<?php

namespace Tests\Feature\Components\Domain;

use App\Models\Asset;
use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionSubcomponentTemplate;
use App\Models\ComponentEvent;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\User;
use App\Services\ComponentLifecycleService;
use App\Services\Components\ComponentExpectedSubcomponentService;
use Tests\TestCase;

class ComponentChildDetachmentTest extends TestCase
{
    public function testChildComponentCanDetachToTrayWithAncestrySnapshot(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $parent = ComponentInstance::factory()->installed($asset->id)->create();
        $child = ComponentInstance::factory()->asChildOf($parent)->create();

        $detached = app(ComponentLifecycleService::class)->removeToTray($child, $actor, [
            'note' => 'Removed from parent board.',
        ]);

        $this->assertSame(ComponentInstance::STATUS_IN_TRANSFER, $detached->status);
        $this->assertNull($detached->parent_component_instance_id);
        $this->assertNull($detached->current_asset_id);
        $this->assertNull($detached->root_asset_id);
        $this->assertSame($actor->id, $detached->held_by_user_id);
        $this->assertSame($parent->id, $detached->ancestry_parent_component_instance_id);
        $this->assertNotNull($detached->ancestry_attached_through_at);
        $this->assertNotNull($detached->ancestry_attached_through_event_id);

        $event = $detached->events()->where('event_type', 'removed_to_tray')->firstOrFail();

        $this->assertSame($event->id, $detached->ancestry_attached_through_event_id);
        $this->assertSame($parent->id, data_get($event->payload_json, 'detached_parent_component_instance_id'));
    }

    public function testChildComponentCanDetachToStockWithAncestrySnapshot(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $stock = ComponentStorageLocation::factory()->stock()->create();
        $parent = ComponentInstance::factory()->installed($asset->id)->create();
        $child = ComponentInstance::factory()->asChildOf($parent)->create();

        $detached = app(ComponentLifecycleService::class)->moveToStock($child, $stock, [
            'performed_by' => $actor,
            'note' => 'Moved to stock after removal.',
        ]);

        $this->assertSame(ComponentInstance::STATUS_IN_STOCK, $detached->status);
        $this->assertNull($detached->parent_component_instance_id);
        $this->assertNull($detached->current_asset_id);
        $this->assertNull($detached->root_asset_id);
        $this->assertSame($stock->id, $detached->storage_location_id);
        $this->assertSame($parent->id, $detached->ancestry_parent_component_instance_id);
        $this->assertNotNull($detached->ancestry_attached_through_at);
        $this->assertNotNull($detached->ancestry_attached_through_event_id);
    }

    public function testDetachingChildDoesNotCopyParentHistory(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $parent = ComponentInstance::factory()->installed($asset->id)->create();
        $child = ComponentInstance::factory()->asChildOf($parent)->create();

        ComponentEvent::query()->create([
            'component_instance_id' => $parent->id,
            'event_type' => 'parent_only_event',
            'note' => 'Parent event should stay on parent.',
            'created_at' => now(),
        ]);

        $detached = app(ComponentLifecycleService::class)->removeToTray($child, $actor);

        $this->assertTrue($parent->fresh('events')->events->contains('event_type', 'parent_only_event'));
        $this->assertFalse($detached->fresh('events')->events->contains('event_type', 'parent_only_event'));
    }

    public function testDetachedChildDoesNotMoveWhenParentMovesLater(): void
    {
        $actor = User::factory()->superuser()->create();
        $sourceAsset = Asset::factory()->create();
        $targetAsset = Asset::factory()->create();
        $stock = ComponentStorageLocation::factory()->stock()->create();
        $parent = ComponentInstance::factory()->installed($sourceAsset->id)->create();
        $child = ComponentInstance::factory()->asChildOf($parent)->create();
        $lifecycle = app(ComponentLifecycleService::class);

        $detached = $lifecycle->moveToStock($child, $stock, [
            'performed_by' => $actor,
        ]);

        $lifecycle->installIntoAsset($parent->fresh(), $targetAsset, [
            'performed_by' => $actor,
        ]);

        $detached = $detached->fresh();

        $this->assertSame(ComponentInstance::STATUS_IN_STOCK, $detached->status);
        $this->assertNull($detached->current_asset_id);
        $this->assertNull($detached->root_asset_id);
        $this->assertSame($stock->id, $detached->storage_location_id);
        $this->assertSame($parent->id, $detached->ancestry_parent_component_instance_id);
    }

    public function testChildTransferToAnotherAssetClosesAncestrySnapshot(): void
    {
        $actor = User::factory()->superuser()->create();
        $sourceAsset = Asset::factory()->create();
        $targetAsset = Asset::factory()->create();
        $parent = ComponentInstance::factory()->installed($sourceAsset->id)->create();
        $child = ComponentInstance::factory()->asChildOf($parent)->create();

        $transferred = app(ComponentLifecycleService::class)->installIntoAsset($child, $targetAsset, [
            'performed_by' => $actor,
            'note' => 'Moved directly to another asset.',
        ]);

        $this->assertSame(ComponentInstance::STATUS_INSTALLED, $transferred->status);
        $this->assertNull($transferred->parent_component_instance_id);
        $this->assertSame($targetAsset->id, $transferred->current_asset_id);
        $this->assertSame($targetAsset->id, $transferred->root_asset_id);
        $this->assertSame($parent->id, $transferred->ancestry_parent_component_instance_id);
        $this->assertNotNull($transferred->ancestry_attached_through_at);
        $this->assertNotNull($transferred->ancestry_attached_through_event_id);
    }

    public function testDetachingMaterializedExpectedChildMovesExpectedStateFromTrackedToRemoved(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $parentDefinition = ComponentDefinition::factory()->create();
        $childDefinition = ComponentDefinition::factory()->create();
        $template = ComponentDefinitionSubcomponentTemplate::factory()->create([
            'parent_component_definition_id' => $parentDefinition->id,
            'child_component_definition_id' => $childDefinition->id,
            'expected_qty' => 1,
        ]);
        $parent = ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $parentDefinition->id,
        ]);
        $child = app(ComponentExpectedSubcomponentService::class)->materializeAttachedChild($parent, $template, $actor, [
            'condition_warning_confirmed' => true,
        ]);

        app(ComponentLifecycleService::class)->moveToStock($child, null, [
            'performed_by' => $actor,
        ]);

        $this->assertDatabaseHas('component_expected_subcomponent_states', [
            'component_instance_id' => $parent->id,
            'component_definition_subcomponent_template_id' => $template->id,
            'materialized_qty' => 0,
            'removed_qty' => 1,
        ]);
    }
}
