<?php

namespace Tests\Feature\Components\Domain;

use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionSubcomponentTemplate;
use App\Models\ComponentExpectedSubcomponentState;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\User;
use App\Services\ComponentEventWriter;
use App\Services\ComponentLifecycleService;
use App\Services\Components\ComponentExpectedSubcomponentService;
use InvalidArgumentException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ComponentHierarchyIntegrityTest extends TestCase
{
    public function testParentMovedOffAssetCannotBeDeletedWhileAChildRemainsLinked(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $stock = ComponentStorageLocation::factory()->stock()->create();
        $parent = ComponentInstance::factory()->installed($asset->id)->create();
        $child = ComponentInstance::factory()->asChildOf($parent)->create();
        $lifecycle = app(ComponentLifecycleService::class);

        $lifecycle->moveToStock($parent, $stock, [
            'performed_by' => $actor,
            'note' => 'Keep the assembly together in stock.',
        ]);

        try {
            $lifecycle->deleteInstance($parent->fresh(), $actor);
            $this->fail('The parent deletion should have been rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'Components with child components cannot be deleted. Detach or delete the child components first.',
                $exception->getMessage()
            );
        }

        $this->assertNotSoftDeleted($parent);
        $this->assertNotSoftDeleted($child);
        $this->assertSame($parent->id, $child->fresh()->parent_component_instance_id);
        $this->assertDatabaseMissing('component_events', [
            'component_instance_id' => $parent->id,
            'event_type' => 'deleted',
        ]);
        $this->assertDatabaseMissing('action_logs', [
            'item_type' => ComponentInstance::class,
            'item_id' => $parent->id,
            'action_type' => 'delete',
        ]);
    }

    public function testExpectedChildReparentTransfersAndRestoresExpectedSlots(): void
    {
        [$actor, $asset, $template, $fromParent, $child] = $this->expectedAssembly();
        $toParent = ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $fromParent->component_definition_id,
            'display_name' => 'Replacement main board',
        ]);
        $lifecycle = app(ComponentLifecycleService::class);

        $lifecycle->reparentWithinAsset($child, $toParent, [
            'performed_by' => $actor,
            'note' => 'Move expected port to replacement board.',
        ]);

        $this->assertExpectedState($fromParent, $template, 0, 0);
        $this->assertExpectedState($toParent, $template, 0, 1);
        $child->refresh();
        $this->assertSame($toParent->id, $child->parent_component_instance_id);
        $this->assertSame($toParent->id, data_get($child->metadata_json, 'parent_component_instance_id'));
        $this->assertSame($fromParent->id, data_get($child->metadata_json, 'origin_parent_component_instance_id'));

        $event = $child->events()->where('event_type', 'reparented')->latest('id')->firstOrFail();
        $this->assertSame(
            $template->id,
            data_get(
                $event->payload_json,
                'expected_subcomponent_state_changes.component_definition_subcomponent_template_id'
            )
        );
        $this->assertSame(
            0,
            data_get($event->payload_json, 'expected_subcomponent_state_changes.from_parent.after.materialized_qty')
        );
        $this->assertSame(
            1,
            data_get($event->payload_json, 'expected_subcomponent_state_changes.to_parent.after.materialized_qty')
        );

        $lifecycle->reparentWithinAsset($child->fresh(), null, [
            'performed_by' => $actor,
            'note' => 'Temporarily place the port at asset root.',
        ]);

        $this->assertExpectedState($toParent, $template, 1, 0);
        $this->assertNull($child->fresh()->parent_component_instance_id);

        $lifecycle->reparentWithinAsset($child->fresh(), $toParent->fresh(), [
            'performed_by' => $actor,
            'note' => 'Restore the expected port to the board.',
        ]);

        $this->assertExpectedState($toParent, $template, 0, 1);
        $this->assertSame($toParent->id, $child->fresh()->parent_component_instance_id);
    }

    public function testExpectedChildCannotBeReparentedToAnIncompatibleParent(): void
    {
        [$actor, $asset, $template, $fromParent, $child] = $this->expectedAssembly();
        $incompatibleParent = ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => ComponentDefinition::factory()->create()->id,
        ]);

        try {
            app(ComponentLifecycleService::class)->reparentWithinAsset($child, $incompatibleParent, [
                'performed_by' => $actor,
            ]);
            $this->fail('The incompatible reparent should have been rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'Expected subcomponents can only be moved to a parent that owns the same expected template.',
                $exception->getMessage()
            );
        }

        $this->assertSame($fromParent->id, $child->fresh()->parent_component_instance_id);
        $this->assertExpectedState($fromParent, $template, 0, 1);
        $this->assertDatabaseMissing('component_expected_subcomponent_states', [
            'component_instance_id' => $incompatibleParent->id,
            'component_definition_subcomponent_template_id' => $template->id,
        ]);
        $this->assertSame(0, $child->events()->where('event_type', 'reparented')->count());
    }

    public function testReparentRollsBackChildAndBothExpectedStatesWhenEventWriteFails(): void
    {
        [$actor, $asset, $template, $fromParent, $child] = $this->expectedAssembly();
        $toParent = ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $fromParent->component_definition_id,
        ]);
        $writer = Mockery::mock(ComponentEventWriter::class);
        $writer->shouldReceive('write')
            ->once()
            ->andThrow(new RuntimeException('Simulated event persistence failure.'));
        $lifecycle = new ComponentLifecycleService($writer);

        try {
            $lifecycle->reparentWithinAsset($child, $toParent, [
                'performed_by' => $actor,
            ]);
            $this->fail('The simulated event failure should have escaped the service.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated event persistence failure.', $exception->getMessage());
        }

        $this->assertSame($fromParent->id, $child->fresh()->parent_component_instance_id);
        $this->assertExpectedState($fromParent, $template, 0, 1);
        $this->assertDatabaseMissing('component_expected_subcomponent_states', [
            'component_instance_id' => $toParent->id,
            'component_definition_subcomponent_template_id' => $template->id,
        ]);
        $this->assertSame(0, $child->events()->where('event_type', 'reparented')->count());
    }

    public function testDeletingTerminalExpectedChildReconcilesStateAndWritesBothAuditTrails(): void
    {
        [$actor, $asset, $template, $parent, $child] = $this->expectedAssembly();
        $destruction = ComponentStorageLocation::factory()->destruction()->create();
        $lifecycle = app(ComponentLifecycleService::class);

        $lifecycle->markDestructionPending($parent, $destruction, [
            'performed_by' => $actor,
            'note' => 'Retire the complete assembly.',
        ]);
        $lifecycle->markDestroyed($parent->fresh(), [
            'performed_by' => $actor,
            'note' => 'Destruction verified.',
        ]);

        $lifecycle->deleteInstance($child->fresh(), $actor, [
            'note' => 'Remove destroyed child record.',
        ]);

        $this->assertSoftDeleted($child);
        $this->assertExpectedState($parent, $template, 1, 0);
        $this->assertDatabaseHas('component_events', [
            'component_instance_id' => $child->id,
            'event_type' => 'deleted',
            'performed_by' => $actor->id,
            'note' => 'Remove destroyed child record.',
        ]);
        $this->assertDatabaseHas('action_logs', [
            'item_type' => ComponentInstance::class,
            'item_id' => $child->id,
            'action_type' => 'delete',
            'created_by' => $actor->id,
            'note' => 'Remove destroyed child record.',
        ]);

        $lifecycle->deleteInstance($parent->fresh(), $actor);

        $this->assertSoftDeleted($parent);
        $this->assertSame(2, Actionlog::query()
            ->where('item_type', ComponentInstance::class)
            ->whereIn('item_id', [$child->id, $parent->id])
            ->where('action_type', 'delete')
            ->count());
    }

    private function expectedAssembly(): array
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $parentDefinition = ComponentDefinition::factory()->create([
            'name' => 'Main board',
        ]);
        $childDefinition = ComponentDefinition::factory()->create([
            'name' => 'USB-C port',
        ]);
        $template = ComponentDefinitionSubcomponentTemplate::factory()->create([
            'parent_component_definition_id' => $parentDefinition->id,
            'child_component_definition_id' => $childDefinition->id,
            'expected_name' => 'USB-C port',
            'expected_qty' => 1,
        ]);
        $parent = ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $parentDefinition->id,
            'display_name' => 'Original main board',
        ]);
        $child = app(ComponentExpectedSubcomponentService::class)->materializeAttachedChild(
            $parent,
            $template,
            $actor,
            [
                'condition_warning_confirmed' => true,
                'note' => 'Materialize expected port.',
            ]
        );

        return [$actor, $asset, $template, $parent, $child];
    }

    private function assertExpectedState(
        ComponentInstance $parent,
        ComponentDefinitionSubcomponentTemplate $template,
        int $removedQty,
        int $materializedQty
    ): void {
        $state = ComponentExpectedSubcomponentState::query()
            ->where('component_instance_id', $parent->id)
            ->where('component_definition_subcomponent_template_id', $template->id)
            ->firstOrFail();

        $this->assertSame($removedQty, $state->removed_qty);
        $this->assertSame($materializedQty, $state->materialized_qty);
    }
}
