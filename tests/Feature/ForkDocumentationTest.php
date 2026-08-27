<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckForSetup;
use App\Models\Setting;
use App\Models\User;
use Tests\TestCase;

class ForkDocumentationTest extends TestCase
{
    public function testApiCompatibilityDocumentIsServedLocally(): void
    {
        $this->withoutMiddleware(CheckForSetup::class);

        $this->get('/help/api-compatibility')
            ->assertOk()
            ->assertSee('API and Compatibility Contract')
            ->assertSee('official Snipe-IT documentation is not the contract')
            ->assertSee('php artisan route:list --path=api/v1')
            ->assertDontSee('snipe-it.readme.io');
    }

    public function testApiNotFoundResponsesPointToTheLocalCompatibilityContract(): void
    {
        $user = User::factory()->create();

        foreach (['/api/v1/', '/api/v1/not-a-real-endpoint'] as $uri) {
            $response = $this->actingAsForApi($user)
                ->getJson($uri)
                ->assertNotFound()
                ->assertJsonPath('status', 'error')
                ->assertJsonPath('payload', null);
            $message = (string) $response->json('message');

            $this->assertStringContainsString(
                '/help/api-compatibility',
                $message,
            );
            $this->assertStringNotContainsString(
                'snipe-it.readme.io',
                $message,
            );
        }
    }

    public function testWebhookBotNameUsesTheConfiguredNameOrForkApplicationName(): void
    {
        config(['app.name' => 'Example Refurbishment Platform']);

        $this->assertSame(
            'Example Refurbishment Platform Bot',
            (new Setting(['webhook_botname' => null]))->webhookBotName(),
        );
        $this->assertSame(
            'Operations Bot',
            (new Setting(['webhook_botname' => ' Operations Bot ']))->webhookBotName(),
        );
    }

    public function testDemoSettingsResetUsesForkNeutralIdentityDefaults(): void
    {
        config(['app.name' => 'Example Refurbishment Platform']);

        $this->artisan('snipeit:demo-settings')
            ->expectsOutput('Resetting the demo settings.')
            ->assertSuccessful();

        $settings = Setting::query()->firstOrFail();

        $this->assertSame('Example Refurbishment Platform Demo', $settings->site_name);
        $this->assertSame('demo@example.invalid', $settings->alert_email);
        $this->assertSame('example.invalid', $settings->email_domain);
        $this->assertNull($settings->logo);
        $this->assertSame(1, (int) $settings->brand);
    }
}
