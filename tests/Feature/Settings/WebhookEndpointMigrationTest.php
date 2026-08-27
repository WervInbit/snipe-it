<?php

namespace Tests\Feature\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WebhookEndpointMigrationTest extends TestCase
{
    public function testMigrationClearsExistingInternalWebhookWithoutLoggingItsSecret(): void
    {
        $setting = Setting::getSettings();
        $endpoint = 'http://127.0.0.1/internal/secret-token';
        Log::spy();

        DB::table('settings')->where('id', $setting->id)->update([
            'webhook_endpoint' => $endpoint,
        ]);

        $migration = require base_path('database/migrations/2026_07_28_121000_clear_unsafe_webhook_endpoints.php');
        $migration->up();
        $migration->up();

        $this->assertNull(
            DB::table('settings')->where('id', $setting->id)->value('webhook_endpoint')
        );

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context) use ($endpoint): bool {
                $logRecord = $message.json_encode($context, JSON_THROW_ON_ERROR);

                return ! str_contains($logRecord, $endpoint)
                    && ! str_contains($logRecord, 'secret-token');
            });
    }

    public function testMigrationPreservesPublicWebhookEndpoint(): void
    {
        $setting = Setting::getSettings();
        $endpoint = 'https://93.184.216.34/services/secret-token';

        DB::table('settings')->where('id', $setting->id)->update([
            'webhook_endpoint' => $endpoint,
        ]);

        $migration = require base_path('database/migrations/2026_07_28_121000_clear_unsafe_webhook_endpoints.php');
        $migration->up();

        $this->assertSame(
            $endpoint,
            DB::table('settings')->where('id', $setting->id)->value('webhook_endpoint')
        );
    }

    public function testMigrationHonorsExplicitInternalTargetEscapeHatch(): void
    {
        config(['app.webhook_allow_internal_targets' => true]);

        $setting = Setting::getSettings();
        $endpoint = 'http://10.0.0.1/internal-hook';

        DB::table('settings')->where('id', $setting->id)->update([
            'webhook_endpoint' => $endpoint,
        ]);

        $migration = require base_path('database/migrations/2026_07_28_121000_clear_unsafe_webhook_endpoints.php');
        $migration->up();

        $this->assertSame(
            $endpoint,
            DB::table('settings')->where('id', $setting->id)->value('webhook_endpoint')
        );
    }
}
