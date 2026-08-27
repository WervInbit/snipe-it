<?php

namespace Tests\Feature\Console;

use App\Models\Asset;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RetiredAssetAutomationContractTest extends TestCase
{
    public function testRetiredAssetNotificationsAreNotScheduled(): void
    {
        $commands = collect($this->app->make(Kernel::class)->resolveConsoleSchedule()->events())
            ->pluck('command')
            ->filter()
            ->implode("\n");

        $this->assertStringNotContainsString('snipeit:expected-checkin', $commands);
        $this->assertStringNotContainsString('snipeit:upcoming-audits', $commands);
        $this->assertStringNotContainsString('snipeit:counter-sync', $commands);
    }

    public function testRetiredCounterSyncCommandCannotMutateHistoricalCounters(): void
    {
        $asset = Asset::factory()->create();
        $asset->forceFill([
            'checkin_counter' => 11,
            'checkout_counter' => 12,
            'requests_counter' => 13,
        ])->saveQuietly();
        $snapshot = $asset->only([
            'checkin_counter',
            'checkout_counter',
            'requests_counter',
        ]);

        $this->artisan('snipeit:counter-sync')
            ->expectsOutput(
                'This command is retired because asset checkout, check-in, and request counters are historical.'
            )
            ->assertExitCode(1);

        $this->assertSame($snapshot, $asset->refresh()->only(array_keys($snapshot)));
        $this->assertStringNotContainsString(
            'snipeit:counter-sync',
            file_get_contents(
                database_path('migrations/2018_05_16_153409_add_first_counter_totals_to_assets.php')
            )
        );
    }

    public function testLdapSyncDoesNotRewriteLocationsForAssignedAssets(): void
    {
        $source = file_get_contents(app_path('Console/Commands/LdapSync.php'));

        $this->assertStringNotContainsString('$user->assets', $source);
        $this->assertStringNotContainsString('$asset->location_id = $user->location_id', $source);
    }

    public function testRetiredAssetNotificationCommandsAndTemplatesAreAbsent(): void
    {
        $commands = Artisan::all();

        $this->assertArrayNotHasKey('snipeit:expected-checkin', $commands);
        $this->assertArrayNotHasKey('snipeit:upcoming-audits', $commands);
        $this->assertFileDoesNotExist(app_path('Console/Commands/SendExpectedCheckinAlerts.php'));
        $this->assertFileDoesNotExist(app_path('Console/Commands/SendUpcomingAuditReport.php'));
        $this->assertFileDoesNotExist(app_path('Mail/SendUpcomingAuditMail.php'));
        $this->assertFileDoesNotExist(app_path('Notifications/ExpectedCheckinAdminNotification.php'));
        $this->assertFileDoesNotExist(app_path('Notifications/ExpectedCheckinNotification.php'));
        $this->assertFalse(view()->exists('notifications.markdown.expected-checkin'));
        $this->assertFalse(view()->exists('notifications.markdown.report-expected-checkins'));
        $this->assertFalse(view()->exists('notifications.markdown.upcoming-audits'));
    }

    public function testSidebarMiddlewareNoLongerQueriesRetiredDeadlines(): void
    {
        $source = file_get_contents(app_path('Http/Middleware/AssetCountForSidebar.php'));

        $this->assertStringNotContainsString('DueForAudit', $source);
        $this->assertStringNotContainsString('OverdueForAudit', $source);
        $this->assertStringNotContainsString('DueForCheckin', $source);
        $this->assertStringNotContainsString('OverdueForCheckin', $source);
        $this->assertStringNotContainsString('total_due_and_overdue_for_audit', $source);
        $this->assertStringNotContainsString('total_due_and_overdue_for_checkin', $source);
    }
}
