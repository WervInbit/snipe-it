<?php

namespace Tests\Feature\Components\Ui;

use App\Models\Asset;
use App\Models\ComponentEvent;
use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionSubcomponentTemplate;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderTask;
use App\Services\ComponentLifecycleService;
use App\Services\Components\ComponentExpectedSubcomponentService;
use Tests\TestCase;

class ShowComponentTest extends TestCase
{
    public function testPageRenders(): void
    {
        $component = ComponentInstance::factory()->inTray(User::factory()->superuser()->create())->create([
            'installed_as' => 'DIMM A',
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('components.show', $component))
            ->assertOk()
            ->assertSee(route('components.install.create', [$component, 'return_to' => route('components.show', $component)]), false)
            ->assertSeeText('Status: In Tray')
            ->assertSeeText('In Stock')
            ->assertSeeText('Needs Attention')
            ->assertSeeText('Damaged')
            ->assertSeeText('Destruction Pending')
            ->assertDontSee('id="componentToTrayModal"', false)
            ->assertDontSee('id="component_install_asset_id"', false)
            ->assertDontSeeText('Installed As')
            ->assertDontSeeText('DIMM A')
            ->assertDontSee('name="storage_location_id"', false)
            ->assertDontSeeText('Save Storage Location')
            ->assertSee('name="notes"', false)
            ->assertSeeText('Save Note')
            ->assertSeeText('Upload photos or files for this component here.');
    }

    public function testInstalledComponentDetailPageShowsToTrayModal(): void
    {
        $user = User::factory()->superuser()->create();
        $asset = \App\Models\Asset::factory()->create();
        $component = ComponentInstance::factory()->installed($asset->id)->create();

        $this->actingAs($user)
            ->get(route('components.show', $component))
            ->assertOk()
            ->assertSee(route('components.remove_to_tray', $component), false)
            ->assertSee('id="componentToTrayModal"', false)
            ->assertSeeText('Status: Attached')
            ->assertSeeText('In Tray')
            ->assertSeeText('Confirm To Tray');
    }

    public function testHistoryShowsLinkedWorkOrderAndTask(): void
    {
        $component = ComponentInstance::factory()->create();
        $workOrder = WorkOrder::factory()->create();
        $task = WorkOrderTask::factory()->for($workOrder)->create([
            'title' => 'Portal Linked Task',
            'work_order_asset_id' => null,
        ]);

        ComponentEvent::query()->create([
            'component_instance_id' => $component->id,
            'event_type' => 'installed',
            'related_work_order_id' => $workOrder->id,
            'related_work_order_task_id' => $task->id,
            'created_at' => now(),
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('components.show', $component))
            ->assertOk()
            ->assertSeeText('Status History')
            ->assertSee($workOrder->work_order_number)
            ->assertSee('Portal Linked Task')
            ->assertSee(route('work-orders.show', $workOrder), false);
    }

    public function testHistoryShowsLinkedFromAndToAssets(): void
    {
        $component = ComponentInstance::factory()->create();
        $fromAsset = Asset::factory()->create();
        $toAsset = Asset::factory()->create();

        ComponentEvent::query()->create([
            'component_instance_id' => $component->id,
            'event_type' => 'installed',
            'from_asset_id' => $fromAsset->id,
            'to_asset_id' => $toAsset->id,
            'created_at' => now(),
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('components.show', $component))
            ->assertOk()
            ->assertSee('<a href="'.e(route('hardware.show', $fromAsset)).'">From asset: '.$fromAsset->present()->name().'</a>', false)
            ->assertSee('<a href="'.e(route('hardware.show', $toAsset)).'">To asset: '.$toAsset->present()->name().'</a>', false);
    }

    public function testComponentNotesCanBeUpdatedFromDetailPage(): void
    {
        $actor = User::factory()->superuser()->create();
        $component = ComponentInstance::factory()->create([
            'notes' => null,
        ]);
        $token = 'note-test-token';

        $this->actingAs($actor)
            ->withSession(['_token' => $token])
            ->put(route('components.update', $component), [
                '_token' => $token,
                'notes' => 'Needs follow-up inspection.',
            ])
            ->assertRedirect(route('components.show', $component))
            ->assertSessionHas('success', 'Component note updated.');

        $this->assertDatabaseHas('component_instances', [
            'id' => $component->id,
            'notes' => 'Needs follow-up inspection.',
            'updated_by' => $actor->id,
        ]);
    }

    public function testLooseComponentDetailPageShowsStorageLocationEditor(): void
    {
        $component = ComponentInstance::factory()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('components.show', $component))
            ->assertOk()
            ->assertSee('name="storage_location_id"', false)
            ->assertSeeText('Save Storage Location');
    }

    public function testComponentDetailShowsAttachedAndExpectedChildStructure(): void
    {
        $asset = Asset::factory()->create();
        $parentDefinition = ComponentDefinition::factory()->create([
            'name' => 'Main Board Assembly',
        ]);
        $childDefinition = ComponentDefinition::factory()->create([
            'name' => 'USB-C Port Board',
            'part_code' => 'USB-C-PORT',
        ]);
        ComponentDefinitionSubcomponentTemplate::factory()->create([
            'parent_component_definition_id' => $parentDefinition->id,
            'child_component_definition_id' => $childDefinition->id,
            'expected_name' => 'USB-C Port Board',
            'expected_qty' => 2,
            'is_required' => true,
            'notes' => 'One on each side.',
        ]);
        ComponentDefinitionSubcomponentTemplate::factory()->create([
            'parent_component_definition_id' => $parentDefinition->id,
            'child_component_definition_id' => null,
            'expected_name' => 'Thermal Pad',
            'expected_qty' => 1,
            'is_required' => false,
        ]);
        $parent = ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $parentDefinition->id,
            'display_name' => 'Main Board Assembly',
        ]);
        $child = ComponentInstance::factory()->asChildOf($parent)->create([
            'component_definition_id' => $childDefinition->id,
            'display_name' => 'Tracked USB-C Port Board',
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('components.show', $parent))
            ->assertOk()
            ->assertSeeText('Child Structure')
            ->assertSeeText('Attached Child Components')
            ->assertSee(route('components.show', $child), false)
            ->assertSee(route('components.remove_to_tray', $child), false)
            ->assertSee(route('components.move_to_stock', $child), false)
            ->assertSeeText('Tracked USB-C Port Board')
            ->assertSeeText($child->component_tag)
            ->assertSeeText('Expected Subcomponents')
            ->assertSeeText('USB-C Port Board')
            ->assertSeeText('USB-C-PORT')
            ->assertSeeText('One on each side.')
            ->assertSeeText('Thermal Pad')
            ->assertSeeText('Freeform');
    }

    public function testComponentDetailCanMaterializeExpectedSubcomponent(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $parentDefinition = ComponentDefinition::factory()->create([
            'name' => 'Main Board Assembly',
        ]);
        $childDefinition = ComponentDefinition::factory()->create([
            'name' => 'USB-C Port Board',
        ]);
        $template = ComponentDefinitionSubcomponentTemplate::factory()->create([
            'parent_component_definition_id' => $parentDefinition->id,
            'child_component_definition_id' => $childDefinition->id,
            'expected_name' => 'USB-C Port Board',
            'expected_qty' => 1,
        ]);
        $parent = ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $parentDefinition->id,
            'display_name' => 'Main Board Assembly',
        ]);

        $this->actingAs($actor)
            ->get(route('components.show', $parent))
            ->assertOk()
            ->assertSee(route('components.expected_subcomponents.materialize', [$parent, $template]), false)
            ->assertSeeText('Remaining: 1')
            ->assertSeeText('Condition warning')
            ->assertSeeText('Track');

        $token = 'expected-subcomponent-track-token';

        $this->actingAs($actor)
            ->withSession(['_token' => $token])
            ->post(route('components.expected_subcomponents.materialize', [$parent, $template]), [
                '_token' => $token,
                'note' => 'Started tracking from detail page.',
            ])
            ->assertRedirect(route('components.show', $parent))
            ->assertSessionHas('warning');

        $this->assertDatabaseMissing('component_instances', [
            'parent_component_instance_id' => $parent->id,
            'component_definition_id' => $childDefinition->id,
        ]);

        $this->actingAs($actor)
            ->withSession(['_token' => $token])
            ->post(route('components.expected_subcomponents.materialize', [$parent, $template]), [
                '_token' => $token,
                'condition_warning_confirmed' => 1,
                'note' => 'Started tracking from detail page.',
            ])
            ->assertRedirect(route('components.show', $parent))
            ->assertSessionHas('success', 'Expected subcomponent tracked.');

        $child = ComponentInstance::query()
            ->where('parent_component_instance_id', $parent->id)
            ->where('component_definition_id', $childDefinition->id)
            ->firstOrFail();

        $this->assertSame('USB-C Port Board', $child->display_name);
        $this->assertSame('Started tracking from detail page.', $child->materialized_reason);

        $this->actingAs($actor)
            ->get(route('components.show', $parent))
            ->assertOk()
            ->assertSee(route('components.show', $child), false)
            ->assertSeeText($child->component_tag)
            ->assertSeeText('Remaining: 0')
            ->assertSeeText('Complete');
    }

    public function testComponentDetailCanCreateCustomChildComponent(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $parent = ComponentInstance::factory()->installed($asset->id)->create([
            'display_name' => 'Main Board Assembly',
        ]);

        $this->actingAs($actor)
            ->get(route('components.show', $parent))
            ->assertOk()
            ->assertSeeText('Add Child Component')
            ->assertSee(route('components.children.store', $parent), false)
            ->assertSeeText('Custom Component')
            ->assertSeeText('Create Child Component');

        $token = 'custom-child-component-token';

        $this->actingAs($actor)
            ->withSession(['_token' => $token])
            ->post(route('components.children.store', $parent), [
                '_token' => $token,
                'creation_mode' => 'custom',
                'display_name' => 'Replacement Thermal Pad',
                'serial' => 'THERM-123',
                'note' => 'Specific thickness captured in the note.',
            ])
            ->assertRedirect(route('components.show', $parent))
            ->assertSessionHas('warning');

        $this->assertDatabaseMissing('component_instances', [
            'parent_component_instance_id' => $parent->id,
            'display_name' => 'Replacement Thermal Pad',
        ]);

        $this->actingAs($actor)
            ->withSession(['_token' => $token])
            ->post(route('components.children.store', $parent), [
                '_token' => $token,
                'creation_mode' => 'custom',
                'display_name' => 'Replacement Thermal Pad',
                'serial' => 'THERM-123',
                'condition_warning_confirmed' => 1,
                'note' => 'Specific thickness captured in the note.',
            ])
            ->assertRedirect(route('components.show', $parent))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Child component created.');

        $child = ComponentInstance::query()
            ->where('parent_component_instance_id', $parent->id)
            ->where('display_name', 'Replacement Thermal Pad')
            ->firstOrFail();

        $this->assertSame($asset->id, $child->current_asset_id);
        $this->assertSame($asset->id, $child->root_asset_id);
        $this->assertSame(ComponentInstance::STATUS_INSTALLED, $child->status);
        $this->assertSame(ComponentInstance::LIFECYCLE_ATTACHED, $child->lifecycle_status);
        $this->assertSame(ComponentInstance::CONDITION_STATUS_NEEDS_ATTENTION, $child->condition_status);
        $this->assertSame(ComponentInstance::SOURCE_MANUAL, $child->source_type);
        $this->assertSame('THERM-123', $child->serial);
        $this->assertSame('Specific thickness captured in the note.', $child->notes);

        $this->actingAs($actor)
            ->get(route('components.show', $parent))
            ->assertOk()
            ->assertSee(route('components.show', $child), false)
            ->assertSeeText('Replacement Thermal Pad');
    }

    public function testComponentDetailCanCreateDefinitionBackedChildComponent(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $parent = ComponentInstance::factory()->installed($asset->id)->create([
            'display_name' => 'Main Board Assembly',
        ]);
        $childDefinition = ComponentDefinition::factory()->create([
            'name' => 'I/O Daughterboard',
            'placement_mode' => ComponentDefinition::PLACEMENT_SUBCOMPONENT_ONLY,
        ]);

        $token = 'definition-child-component-token';

        $this->actingAs($actor)
            ->withSession(['_token' => $token])
            ->post(route('components.children.store', $parent), [
                '_token' => $token,
                'creation_mode' => 'definition',
                'component_definition_id' => $childDefinition->id,
                'condition_warning_confirmed' => 1,
                'note' => 'Tracked from repair bench.',
            ])
            ->assertRedirect(route('components.show', $parent))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Child component created.');

        $this->assertDatabaseHas('component_instances', [
            'parent_component_instance_id' => $parent->id,
            'component_definition_id' => $childDefinition->id,
            'current_asset_id' => $asset->id,
            'root_asset_id' => $asset->id,
            'display_name' => 'I/O Daughterboard',
            'notes' => 'Tracked from repair bench.',
        ]);
    }

    public function testComponentDetailRejectsAssetOnlyDefinitionForChildComponent(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $parent = ComponentInstance::factory()->installed($asset->id)->create();
        $assetOnlyDefinition = ComponentDefinition::factory()->create([
            'name' => 'Asset Only Battery Pack',
            'placement_mode' => ComponentDefinition::PLACEMENT_ASSET_ONLY,
        ]);
        $token = 'asset-only-child-component-token';

        $this->actingAs($actor)
            ->get(route('components.show', $parent))
            ->assertOk()
            ->assertDontSeeText('Asset Only Battery Pack');

        $this->actingAs($actor)
            ->withSession(['_token' => $token])
            ->post(route('components.children.store', $parent), [
                '_token' => $token,
                'creation_mode' => 'definition',
                'component_definition_id' => $assetOnlyDefinition->id,
                'condition_warning_confirmed' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('component_definition_id');

        $this->assertDatabaseMissing('component_instances', [
            'parent_component_instance_id' => $parent->id,
            'component_definition_id' => $assetOnlyDefinition->id,
        ]);
    }

    public function testComponentDetailKeepsDetachedExpectedChildVisible(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $stock = ComponentStorageLocation::factory()->stock()->create([
            'name' => 'Bench Stock',
        ]);
        $parentDefinition = ComponentDefinition::factory()->create([
            'name' => 'Main Board Assembly',
        ]);
        $childDefinition = ComponentDefinition::factory()->create([
            'name' => 'USB-C Port Board',
        ]);
        $template = ComponentDefinitionSubcomponentTemplate::factory()->create([
            'parent_component_definition_id' => $parentDefinition->id,
            'child_component_definition_id' => $childDefinition->id,
            'expected_name' => 'USB-C Port Board',
            'expected_qty' => 1,
        ]);
        $parent = ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $parentDefinition->id,
            'display_name' => 'Main Board Assembly',
        ]);
        $child = app(ComponentExpectedSubcomponentService::class)->materializeAttachedChild($parent, $template, $actor, [
            'condition_warning_confirmed' => true,
        ]);

        app(ComponentLifecycleService::class)->moveToStock($child, $stock, [
            'performed_by' => $actor,
            'note' => 'Removed and kept for reuse.',
        ]);

        $this->actingAs($actor)
            ->get(route('components.show', $parent))
            ->assertOk()
            ->assertSeeText('Removed Expected Child Components')
            ->assertSee(route('components.show', $child), false)
            ->assertSeeText($child->component_tag)
            ->assertSeeText('Bench Stock')
            ->assertSeeText('Tracked: 0')
            ->assertSeeText('Removed: 1')
            ->assertSeeText('Remaining: 0');
    }

    public function testComponentDetailChildStructureIsStableWithoutChildrenOrExpectedRows(): void
    {
        $component = ComponentInstance::factory()->create([
            'component_definition_id' => null,
            'display_name' => 'Loose Custom Component',
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('components.show', $component))
            ->assertOk()
            ->assertSeeText('Child Structure')
            ->assertSeeText('No child components attached.')
            ->assertSeeText('No expected subcomponents defined.');
    }

    public function testSoldReturnedComponentDetailStillOffersInstall(): void
    {
        $component = ComponentInstance::factory()->create([
            'status' => ComponentInstance::STATUS_SOLD_RETURNED,
            'lifecycle_status' => ComponentInstance::LIFECYCLE_SOLD_RETURNED,
            'storage_location_id' => null,
            'current_asset_id' => null,
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('components.show', $component))
            ->assertOk()
            ->assertSee(route('components.install.create', [$component, 'return_to' => route('components.show', $component)]), false)
            ->assertSeeText('Install');
    }
}
