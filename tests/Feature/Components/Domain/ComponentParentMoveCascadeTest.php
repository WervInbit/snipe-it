<?php

namespace Tests\Feature\Components\Domain;

use App\Models\Asset;
use App\Models\ComponentEvent;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\User;
use App\Services\ComponentLifecycleService;
use Tests\TestCase;

class ComponentParentMoveCascadeTest extends TestCase
{
    public function testParentTransferCarriesAttachedChild(): void
    {
        $actor = User::factory()->superuser()->create();
        $sourceAsset = Asset::factory()->create();
        $targetAsset = Asset::factory()->create();
        $parent = ComponentInstance::factory()->installed($sourceAsset->id)->create([
            'display_name' => 'Main board',
        ]);
        $child = ComponentInstance::factory()->asChildOf($parent)->create([
            'display_name' => 'USB-C daughterboard',
        ]);

        app(ComponentLifecycleService::class)->installIntoAsset($parent, $targetAsset, [
            'performed_by' => $actor,
            'note' => 'Moved assembly to donor asset.',
        ]);

        $child = $child->fresh();

        $this->assertSame($parent->id, $child->parent_component_instance_id);
        $this->assertSame($targetAsset->id, $child->current_asset_id);
        $this->assertSame($targetAsset->id, $child->root_asset_id);
        $this->assertSame(ComponentInstance::STATUS_INSTALLED, $child->status);
    }

    public function testParentRemovalToTrayCarriesAttachedChildOffAsset(): void
    {
        $actor = User::factory()->superuser()->create();
        $sourceAsset = Asset::factory()->create();
        $parent = ComponentInstance::factory()->installed($sourceAsset->id)->create();
        $child = ComponentInstance::factory()->asChildOf($parent)->create();

        app(ComponentLifecycleService::class)->removeToTray($parent, $actor, [
            'note' => 'Remove assembly to tray.',
        ]);

        $child->refresh();

        $this->assertSame($parent->id, $child->parent_component_instance_id);
        $this->assertNull($child->current_asset_id);
        $this->assertNull($child->root_asset_id);
        $this->assertNull($child->storage_location_id);
        $this->assertSame(ComponentInstance::STATUS_INSTALLED, $child->status);
        $this->assertSame(ComponentInstance::LIFECYCLE_ATTACHED, $child->lifecycle_status);

        $this->assertDatabaseHas('component_events', [
            'component_instance_id' => $child->id,
            'event_type' => 'moved_with_parent',
            'from_asset_id' => $sourceAsset->id,
            'to_asset_id' => null,
        ]);
    }

    public function testParentMoveToStockCarriesAttachedChildOffAsset(): void
    {
        $actor = User::factory()->superuser()->create();
        $sourceAsset = Asset::factory()->create();
        $stock = ComponentStorageLocation::factory()->stock()->create();
        $parent = ComponentInstance::factory()->installed($sourceAsset->id)->create();
        $child = ComponentInstance::factory()->asChildOf($parent)->create();

        app(ComponentLifecycleService::class)->moveToStock($parent, $stock, [
            'performed_by' => $actor,
            'note' => 'Move assembly to stock.',
        ]);

        $child->refresh();

        $this->assertSame($parent->id, $child->parent_component_instance_id);
        $this->assertNull($child->current_asset_id);
        $this->assertNull($child->root_asset_id);
        $this->assertNull($child->storage_location_id);
        $this->assertSame(ComponentInstance::STATUS_INSTALLED, $child->status);
        $this->assertSame(ComponentInstance::LIFECYCLE_ATTACHED, $child->lifecycle_status);
    }

    public function testParentDestructionPendingLocksAttachedChildOffAsset(): void
    {
        $actor = User::factory()->superuser()->create();
        $sourceAsset = Asset::factory()->create();
        $destruction = ComponentStorageLocation::factory()->destruction()->create();
        $parent = ComponentInstance::factory()->installed($sourceAsset->id)->create();
        $child = ComponentInstance::factory()->asChildOf($parent)->create();

        app(ComponentLifecycleService::class)->markDestructionPending($parent, $destruction, [
            'performed_by' => $actor,
            'note' => 'Retire assembly.',
        ]);

        $child->refresh();

        $this->assertSame($parent->id, $child->parent_component_instance_id);
        $this->assertNull($child->current_asset_id);
        $this->assertNull($child->root_asset_id);
        $this->assertSame($destruction->id, $child->storage_location_id);
        $this->assertSame(ComponentInstance::STATUS_DESTRUCTION_PENDING, $child->status);
        $this->assertSame(ComponentInstance::LIFECYCLE_DESTRUCTION_PENDING, $child->lifecycle_status);
    }

    public function testParentDestroyedCascadesToPendingAttachedChild(): void
    {
        $actor = User::factory()->superuser()->create();
        $sourceAsset = Asset::factory()->create();
        $destruction = ComponentStorageLocation::factory()->destruction()->create();
        $parent = ComponentInstance::factory()->installed($sourceAsset->id)->create();
        $child = ComponentInstance::factory()->asChildOf($parent)->create();
        $service = app(ComponentLifecycleService::class);

        $service->markDestructionPending($parent, $destruction, [
            'performed_by' => $actor,
            'note' => 'Retire assembly.',
        ]);
        $service->markDestroyed($parent->fresh(), [
            'performed_by' => $actor,
            'note' => 'Verified destroyed.',
        ]);

        $child->refresh();

        $this->assertSame($parent->id, $child->parent_component_instance_id);
        $this->assertSame(ComponentInstance::STATUS_DESTROYED_RECYCLED, $child->status);
        $this->assertSame(ComponentInstance::LIFECYCLE_DESTROYED, $child->lifecycle_status);
        $this->assertNotNull($child->destroyed_at);
    }

    public function testParentTransferWritesParentSummaryAndChildMoveEvent(): void
    {
        $actor = User::factory()->superuser()->create();
        $sourceAsset = Asset::factory()->create();
        $targetAsset = Asset::factory()->create();
        $parent = ComponentInstance::factory()->installed($sourceAsset->id)->create();
        $child = ComponentInstance::factory()->asChildOf($parent)->create();

        app(ComponentLifecycleService::class)->installIntoAsset($parent, $targetAsset, [
            'performed_by' => $actor,
            'note' => 'Moved with child event coverage.',
        ]);

        $parentEvent = ComponentEvent::query()
            ->where('component_instance_id', $parent->id)
            ->where('event_type', 'installed')
            ->latest('id')
            ->firstOrFail();
        $childEvent = ComponentEvent::query()
            ->where('component_instance_id', $child->id)
            ->where('event_type', 'moved_with_parent')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(1, data_get($parentEvent->payload_json, 'moved_child_count'));
        $this->assertSame([$child->id], data_get($parentEvent->payload_json, 'moved_child_component_ids'));
        $this->assertSame($parent->id, data_get($childEvent->payload_json, 'parent_component_instance_id'));
        $this->assertSame($parentEvent->id, data_get($childEvent->payload_json, 'parent_component_event_id'));
        $this->assertSame($sourceAsset->id, $childEvent->from_asset_id);
        $this->assertSame($targetAsset->id, $childEvent->to_asset_id);
    }

    public function testDetachedStockChildDoesNotMoveWithParent(): void
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

        $this->assertNull($detached->parent_component_instance_id);
        $this->assertNull($detached->current_asset_id);
        $this->assertNull($detached->root_asset_id);
        $this->assertSame(ComponentInstance::STATUS_IN_STOCK, $detached->status);
        $this->assertSame($stock->id, $detached->storage_location_id);
    }

    public function testDetachedTrayChildDoesNotMoveWithParent(): void
    {
        $actor = User::factory()->superuser()->create();
        $sourceAsset = Asset::factory()->create();
        $targetAsset = Asset::factory()->create();
        $parent = ComponentInstance::factory()->installed($sourceAsset->id)->create();
        $child = ComponentInstance::factory()->asChildOf($parent)->create();
        $lifecycle = app(ComponentLifecycleService::class);

        $detached = $lifecycle->removeToTray($child, $actor);

        $lifecycle->installIntoAsset($parent->fresh(), $targetAsset, [
            'performed_by' => $actor,
        ]);

        $detached = $detached->fresh();

        $this->assertNull($detached->parent_component_instance_id);
        $this->assertNull($detached->current_asset_id);
        $this->assertNull($detached->root_asset_id);
        $this->assertSame(ComponentInstance::STATUS_IN_TRANSFER, $detached->status);
        $this->assertSame($actor->id, $detached->held_by_user_id);
    }
}
