<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\User;
use App\Services\ComponentLifecycleService;
use Tests\TestCase;

class ComponentHistoryTest extends TestCase
{
    public function testAssetHistoryShowsInstalledRemovedAndPostRemovalEventsForDeletedComponents(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $stock = ComponentStorageLocation::factory()->stock()->create();

        $component = ComponentInstance::factory()->inTray($actor)->create([
            'display_name' => '32GB DDR4',
            'source_asset_id' => null,
        ]);

        $service = app(ComponentLifecycleService::class);
        $service->installIntoAsset($component, $asset, [
            'performed_by' => $actor,
            'installed_as' => 'RAM Slot 1',
            'note' => 'Installed during intake.',
        ]);
        $service->removeToTray($component->fresh(), $actor, [
            'note' => 'Removed for teardown.',
        ]);
        $service->moveToStock($component->fresh(), $stock, [
            'performed_by' => $actor,
            'note' => 'Moved to stock after removal.',
        ]);

        $component->delete();

        $response = $this->actingAs($actor)->get(route('hardware.show', $asset));

        $response->assertOk();
        $response->assertSee($component->component_tag);
        $response->assertSee('installed');
        $response->assertSee('removed to tray');
        $response->assertSee('moved to stock');
        $response->assertSee('Deleted');
    }
}
