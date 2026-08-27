<?php

namespace Tests\Feature\Console;

use App\Models\Asset;
use App\Models\CheckoutAcceptance;
use App\Models\LicenseSeat;
use App\Models\User;
use App\Services\Users\UserRestoreBackupService;
use Tests\TestCase;

class RestoreDeletedUsersTest extends TestCase
{
    public function testInvalidDateRangeFailsBeforeBackupOrMutation(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $this->mock(UserRestoreBackupService::class)
            ->shouldNotReceive('run');

        $this->artisan('snipeit:restore-users', [
            '--start_date' => now()->format('Y-m-d H:i:s'),
            '--end_date' => now()->subDay()->format('Y-m-d H:i:s'),
        ])->assertExitCode(1);

        $this->assertSoftDeleted($user);
    }

    public function testBackupFailureLeavesEveryUserAndAssignmentUntouched(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->assignedToUser($user)->create();
        $licenseSeat = LicenseSeat::factory()->assignedToUser($user)->create();
        $user->delete();

        $this->mock(UserRestoreBackupService::class)
            ->shouldReceive('run')
            ->once()
            ->andReturnFalse();

        $this->artisan('snipeit:restore-users', $this->dateRange())
            ->expectsOutput('Backup failed; no users were restored.')
            ->assertExitCode(1);

        $this->assertSoftDeleted($user);
        $this->assertSame($user->id, $asset->refresh()->assigned_to);
        $this->assertSame($user->id, $licenseSeat->refresh()->assigned_to);
    }

    public function testRestoreClearsRetiredAssetAssignmentWithoutReplayingHistoricalAssignments(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->assignedToUser($user)->create([
            'accepted' => 'pending',
            'expected_checkin' => now()->addDay(),
        ]);
        $acceptance = CheckoutAcceptance::factory()->pending()->create([
            'checkoutable_type' => Asset::class,
            'checkoutable_id' => $asset->id,
            'assigned_to_id' => $user->id,
        ]);
        $licenseSeat = LicenseSeat::factory()->assignedToUser($user)->create();
        $user->delete();

        $this->mock(UserRestoreBackupService::class)
            ->shouldReceive('run')
            ->once()
            ->andReturnTrue();

        $this->artisan('snipeit:restore-users', $this->dateRange())
            ->expectsOutput('1 users restored; historical checkout state was not replayed.')
            ->assertExitCode(0);

        $this->assertNotSoftDeleted($user);
        $asset->refresh();
        $this->assertNull($asset->assigned_to);
        $this->assertNull($asset->assigned_type);
        $this->assertNull($asset->accepted);
        $this->assertNull($asset->expected_checkin);
        $this->assertSoftDeleted($acceptance);
        $this->assertSame($user->id, $licenseSeat->refresh()->assigned_to);
    }

    /**
     * @return array<string, string>
     */
    private function dateRange(): array
    {
        return [
            '--start_date' => now()->subDay()->format('Y-m-d H:i:s'),
            '--end_date' => now()->addDay()->format('Y-m-d H:i:s'),
        ];
    }
}
