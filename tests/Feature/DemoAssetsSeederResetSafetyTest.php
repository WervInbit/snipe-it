<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\ComponentEvent;
use App\Models\ComponentInstance;
use App\Models\WorkOrderAsset;
use Database\Seeders\DemoAssetsSeeder;
use ReflectionMethod;
use Tests\TestCase;

class DemoAssetsSeederResetSafetyTest extends TestCase
{
    public function test_demo_reset_removes_disposable_component_hierarchies_without_reusing_asset_ids(): void
    {
        $asset = Asset::factory()->create([
            'asset_tag' => 'RESET-SOURCE-ASSET',
            'serial' => 'RESET-SOURCE-SERIAL',
        ]);
        $parent = ComponentInstance::factory()->installed($asset->id)->create([
            'source_asset_id' => $asset->id,
        ]);
        $child = ComponentInstance::factory()->asChildOf($parent)->create([
            'source_asset_id' => $asset->id,
        ]);
        $stockComponent = ComponentInstance::factory()->create();

        foreach ([$parent, $child] as $component) {
            $component->events()->create([
                'event_type' => 'installed',
                'to_status' => ComponentInstance::STATUS_INSTALLED,
                'to_asset_id' => $asset->id,
                'created_at' => now(),
            ]);
        }

        $stockComponent->events()->create([
            'event_type' => 'created',
            'to_status' => ComponentInstance::STATUS_IN_STOCK,
            'to_storage_location_id' => $stockComponent->storage_location_id,
            'created_at' => now(),
        ]);

        $workOrderAsset = WorkOrderAsset::factory()->create([
            'asset_id' => $asset->id,
            'customer_label' => 'Customer laptop',
            'asset_tag_snapshot' => 'SNAPSHOT-TAG',
            'serial_snapshot' => 'SNAPSHOT-SERIAL',
            'qr_reference' => 'SNAPSHOT-QR',
            'status' => 'in_progress',
            'sort_order' => 7,
        ]);

        $reset = new ReflectionMethod(DemoAssetsSeeder::class, 'resetTables');
        $reset->setAccessible(true);
        $reset->invoke(new DemoAssetsSeeder());

        $this->assertDatabaseMissing('assets', ['id' => $asset->id]);
        $this->assertSame(0, ComponentInstance::withTrashed()->count());
        $this->assertSame(0, ComponentEvent::query()->count());
        $this->assertDatabaseMissing('component_instances', ['id' => $parent->id]);
        $this->assertDatabaseMissing('component_instances', ['id' => $child->id]);
        $this->assertDatabaseMissing('component_instances', ['id' => $stockComponent->id]);
        $this->assertDatabaseMissing('component_instances', [
            'status' => ComponentInstance::STATUS_INSTALLED,
        ]);
        $this->assertDatabaseMissing('component_instances', [
            'lifecycle_status' => ComponentInstance::LIFECYCLE_ATTACHED,
        ]);
        $this->assertDatabaseHas('work_order_assets', [
            'id' => $workOrderAsset->id,
            'asset_id' => null,
            'customer_label' => 'Customer laptop',
            'asset_tag_snapshot' => 'SNAPSHOT-TAG',
            'serial_snapshot' => 'SNAPSHOT-SERIAL',
            'qr_reference' => 'SNAPSHOT-QR',
            'status' => 'in_progress',
            'sort_order' => 7,
        ]);

        $replacement = Asset::factory()->create();

        $this->assertGreaterThan($asset->id, $replacement->id);
    }
}
