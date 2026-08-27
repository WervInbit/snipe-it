<?php

namespace Tests\Feature\Assets;

use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\CheckoutAcceptance;
use App\Models\User;
use Tests\TestCase;

class AssetRestoreCleanupTest extends TestCase
{
    public function test_web_restore_clears_stale_assignment_state_without_removing_history(): void
    {
        [$asset, $pendingAcceptance, $historicalLog] = $this->createStaleDeletedAsset();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('restore/hardware', $asset))
            ->assertRedirectToRoute('hardware.index')
            ->assertSessionHas('success');

        $this->assertRestoredAssetWasCleaned($asset, $pendingAcceptance, $historicalLog);
    }

    public function test_api_restore_clears_stale_assignment_state_without_removing_history(): void
    {
        [$asset, $pendingAcceptance, $historicalLog] = $this->createStaleDeletedAsset();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.assets.restore', $asset))
            ->assertOk()
            ->assertStatusMessageIs('success');

        $this->assertRestoredAssetWasCleaned($asset, $pendingAcceptance, $historicalLog);
    }

    public function test_bulk_restore_clears_stale_assignment_state_without_removing_history(): void
    {
        [$asset, $pendingAcceptance, $historicalLog] = $this->createStaleDeletedAsset();

        $this->actingAs(User::factory()->viewAssets()->deleteAssets()->editAssets()->create())
            ->post(route('hardware/bulkrestore'), [
                'ids' => [$asset->id],
                'bulk_actions' => 'restore',
            ])
            ->assertRedirectToRoute('hardware.index')
            ->assertSessionHas('success');

        $this->assertRestoredAssetWasCleaned($asset, $pendingAcceptance, $historicalLog);
    }

    /**
     * @return array{Asset, CheckoutAcceptance, Actionlog}
     */
    private function createStaleDeletedAsset(): array
    {
        $assignedUser = User::factory()->create();
        $asset = Asset::factory()->assignedToUser($assignedUser)->create([
            'accepted' => 'pending',
            'expected_checkin' => now()->addDay(),
        ]);
        $pendingAcceptance = CheckoutAcceptance::factory()->pending()->create([
            'checkoutable_type' => Asset::class,
            'checkoutable_id' => $asset->id,
            'assigned_to_id' => $assignedUser->id,
        ]);
        $historicalLog = Actionlog::query()
            ->where('item_type', Asset::class)
            ->where('item_id', $asset->id)
            ->where('action_type', 'checkout')
            ->firstOrFail();

        $asset->delete();
        $this->assertSoftDeleted($asset);

        return [$asset, $pendingAcceptance, $historicalLog];
    }

    private function assertRestoredAssetWasCleaned(
        Asset $asset,
        CheckoutAcceptance $pendingAcceptance,
        Actionlog $historicalLog
    ): void {
        $restoredAsset = Asset::query()->findOrFail($asset->id);

        $this->assertNull($restoredAsset->assigned_to);
        $this->assertNull($restoredAsset->assigned_type);
        $this->assertNull($restoredAsset->accepted);
        $this->assertNull($restoredAsset->expected_checkin);
        $this->assertSoftDeleted($pendingAcceptance);
        $this->assertDatabaseHas('action_logs', [
            'id' => $historicalLog->id,
            'item_type' => Asset::class,
            'item_id' => $asset->id,
            'action_type' => 'checkout',
            'deleted_at' => null,
        ]);
    }
}
