<?php

namespace Tests\Feature\Components\Api;

use App\Models\Asset;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\User;
use Tests\TestCase;

class ComponentLifecycleActionTest extends TestCase
{
    public function testComponentCanBeMovedToTrayAndInstalledViaApi(): void
    {
        $actor = User::factory()
            ->viewComponents()
            ->moveComponents()
            ->installComponents()
            ->create();

        $sourceAsset = Asset::factory()->create();
        $targetAsset = Asset::factory()->create();

        $component = ComponentInstance::factory()->installed($sourceAsset->id)->create([
            'source_asset_id' => $sourceAsset->id,
        ]);

        $this->actingAsForApi($actor)
            ->postJson(route('api.components.remove_to_tray', $component), [
                'note' => 'Removed during rebuild.',
            ])
            ->assertStatusMessageIs('success');

        $component->refresh();
        $this->assertSame(ComponentInstance::STATUS_IN_TRANSFER, $component->status);
        $this->assertSame($actor->id, $component->held_by_user_id);
        $this->assertNull($component->current_asset_id);

        $this->actingAsForApi($actor)
            ->postJson(route('api.components.install', $component), [
                'asset_id' => $targetAsset->id,
                'installed_as' => 'SSD Bay 1',
                'note' => 'Installed into replacement unit.',
            ])
            ->assertStatusMessageIs('success');

        $component->refresh();
        $this->assertSame(ComponentInstance::STATUS_INSTALLED, $component->status);
        $this->assertSame($targetAsset->id, $component->current_asset_id);
        $this->assertSame('SSD Bay 1', $component->installed_as);
        $this->assertNull($component->held_by_user_id);
    }

    public function testComponentCanBeMovedToStockNeedingVerificationViaApi(): void
    {
        $actor = User::factory()
            ->viewComponents()
            ->moveComponents()
            ->verifyComponents()
            ->create();

        $stock = ComponentStorageLocation::factory()->stock()->create();
        $verification = ComponentStorageLocation::factory()->verification()->create();
        $component = ComponentInstance::factory()->inTray($actor)->create();

        $this->actingAsForApi($actor)
            ->postJson(route('api.components.move_to_stock', $component), [
                'storage_location_id' => $stock->id,
                'needs_verification' => true,
                'verification_location_id' => $verification->id,
                'note' => 'Needs a test pass before reuse.',
            ])
            ->assertStatusMessageIs('success');

        $component->refresh();
        $this->assertSame(ComponentInstance::STATUS_NEEDS_VERIFICATION, $component->status);
        $this->assertSame($verification->id, $component->storage_location_id);
        $this->assertNull($component->held_by_user_id);
    }
}
