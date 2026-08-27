<?php

namespace Tests\Feature\Livewire;

use App\Livewire\SlackSettingsForm;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class SlackSettingsFormTest extends TestCase
{
    public function testWebhookSettingsPageRenders(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('settings.slack.index'))
            ->assertOk()
            ->assertSee(trans('admin/settings/general.webhook_title'))
            ->assertSee(trans('admin/settings/general.webhook_endpoint', [
                'app' => trans('admin/settings/general.slack'),
            ]));
    }

    public function testWebhookTestRejectsInternalTargetWithoutSendingRequest(): void
    {
        Http::fake();

        $message = trans('validation.external_url', ['attribute' => 'webhook endpoint']);

        Livewire::test(SlackSettingsForm::class)
            ->set('webhook_selected', 'google')
            ->set('webhook_endpoint', 'http://127.0.0.1/internal')
            ->call('googleWebhookTest')
            ->assertSet('isDisabled', 'disabled')
            ->assertSee($message);

        Http::assertNothingSent();
    }

    public function testWebhookTestDoesNotFollowRedirectsOrExposeSecretPath(): void
    {
        Http::fake([
            'https://93.184.216.34/*' => Http::response('', 302, [
                'Location' => 'http://127.0.0.1/internal',
            ]),
        ]);

        $secretEndpoint = 'https://93.184.216.34/services/secret-token';
        $message = trans('admin/settings/message.webhook.error_redirect', [
            'endpoint' => 'https://93.184.216.34',
        ]);

        $component = Livewire::test(SlackSettingsForm::class)
            ->set('webhook_selected', 'google')
            ->set('webhook_endpoint', $secretEndpoint)
            ->call('googleWebhookTest')
            ->assertSet('isDisabled', 'disabled')
            ->assertSee($message);

        $this->assertMatchesRegularExpression(
            '/<div class="alert alert-danger fade in">\s*'.preg_quote(e($message), '/').'\s*<\/div>/',
            $component->html()
        );

        Http::assertSentCount(1);
    }

    public function testWebhookFailureMessageDoesNotExposeTransportDetails(): void
    {
        Http::fake(function (): never {
            throw new RuntimeException('sensitive transport detail');
        });

        $message = trans('admin/settings/message.webhook.error_safe', [
            'app' => trans('admin/settings/general.google_workspaces'),
        ]);

        $component = Livewire::test(SlackSettingsForm::class)
            ->set('webhook_selected', 'google')
            ->set('webhook_endpoint', 'https://93.184.216.34/services/secret-token')
            ->call('googleWebhookTest')
            ->assertSee($message)
            ->assertDontSee('sensitive transport detail');

        $this->assertMatchesRegularExpression(
            '/<div class="alert alert-danger fade in">\s*'.preg_quote(e($message), '/').'\s*<\/div>/',
            $component->html()
        );
    }
}
