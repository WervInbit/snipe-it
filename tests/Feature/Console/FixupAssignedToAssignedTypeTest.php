<?php

namespace Tests\Feature\Console;

use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\CheckoutAcceptance;
use App\Models\User;
use Tests\TestCase;

class FixupAssignedToAssignedTypeTest extends TestCase
{
    public function testCheckoutLogsCannotRecreateRetiredAssignmentType()
    {
        $asset = Asset::factory()->create();
        $user = User::factory()->create();

        $checkoutLog = Actionlog::factory()->create([
            'action_type' => 'checkout',
            'item_id' => $asset->id,
            'item_type' => Asset::class,
            'target_id' => $user->id,
            'target_type' => User::class,
        ]);
        $asset->forceFill([
            'assigned_to' => $user->id,
            'assigned_type' => null,
        ])->saveQuietly();

        $this->artisan('snipeit:assigned-to-fixup --debug')->assertExitCode(0);

        $this->assertNull($asset->fresh()->assigned_to);
        $this->assertNull($asset->fresh()->assigned_type);
        $this->assertDatabaseHas('action_logs', [
            'id' => $checkoutLog->id,
            'item_id' => $asset->id,
            'item_type' => Asset::class,
            'action_type' => 'checkout',
        ]);
    }

    public function testPartiallyAssignedAssetIsClearedWithoutCheckoutHistory(): void
    {
        $asset = Asset::factory()->create();
        $user = User::factory()->create();
        $asset->forceFill([
            'assigned_to' => $user->id,
            'assigned_type' => null,
        ])->saveQuietly();

        $this->artisan('snipeit:assigned-to-fixup --debug')->assertExitCode(0);

        $this->assertNull($asset->fresh()->assigned_to);
        $this->assertNull($asset->fresh()->assigned_type);
    }

    public function testAssignedTypeFixupUsesLegacyCleanupSemantics(): void
    {
        $asset = Asset::factory()->create();
        $user = User::factory()->create();
        $pendingAcceptance = CheckoutAcceptance::factory()->pending()->create([
            'checkoutable_type' => Asset::class,
            'checkoutable_id' => $asset->id,
            'assigned_to_id' => $user->id,
        ]);
        $completedAcceptance = CheckoutAcceptance::factory()->accepted()->create([
            'checkoutable_type' => Asset::class,
            'checkoutable_id' => $asset->id,
            'assigned_to_id' => $user->id,
        ]);
        $historicalLog = Actionlog::factory()->create([
            'action_type' => 'checkout',
            'item_id' => $asset->id,
            'item_type' => Asset::class,
            'target_id' => $user->id,
            'target_type' => User::class,
        ]);

        $asset->forceFill([
            'assigned_to' => null,
            'assigned_type' => User::class,
            'accepted' => 'pending',
            'expected_checkin' => now()->addDay(),
        ])->saveQuietly();

        $this->artisan('snipeit:assigned-type-fixup')->assertExitCode(0);

        $asset->refresh();

        $this->assertNull($asset->assigned_to);
        $this->assertNull($asset->assigned_type);
        $this->assertNull($asset->accepted);
        $this->assertNull($asset->expected_checkin);
        $this->assertSoftDeleted($pendingAcceptance);
        $this->assertNotSoftDeleted($completedAcceptance);
        $this->assertNotNull($completedAcceptance->fresh()->accepted_at);
        $this->assertDatabaseHas('action_logs', [
            'id' => $historicalLog->id,
            'item_id' => $asset->id,
            'item_type' => Asset::class,
            'action_type' => 'checkout',
        ]);
    }
}

