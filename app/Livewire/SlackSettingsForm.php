<?php

namespace App\Livewire;

use App\Helpers\Helper;
use App\Models\Setting;
use App\Rules\ExternalUrl;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Component;

class SlackSettingsForm extends Component
{
    public $webhook_endpoint;
    public $webhook_channel;
    public $webhook_botname;
    public $isDisabled ='disabled' ;
    public $webhook_name;
    public $webhook_link;
    public $webhook_placeholder;
    public $webhook_icon;
    public $webhook_selected;
    public $teams_webhook_deprecated;
    public array $webhook_text;

    public Setting $setting;

    public $save_button;

    public $webhook_test;

    public $webhook_endpoint_rules;

    public $webhook_options;

    protected function rules(): array
    {
        return [
            'webhook_selected' => 'required|in:slack,general,google,microsoft',
            'webhook_endpoint' => [
                'nullable',
                'required_with:webhook_channel',
                'url',
                'max:2048',
                new ExternalUrl,
            ],
            'webhook_channel' => 'required_with:webhook_endpoint|starts_with:#|nullable',
            'webhook_botname' => 'string|max:255|nullable',
        ];
    }

    public function mount()
    {
        $this->webhook_text= [
             "slack" => array(
                "name" => trans('admin/settings/general.slack') ,
                "icon" => 'fab fa-slack',
                "placeholder" => "https://hooks.slack.com/services/XXXXXXXXXXXXXXXXXXXXX",
                "link" => 'https://api.slack.com/messaging/webhooks',
                "test" => "testWebhook"
        ),
            "general"=> array(
                "name" => trans('admin/settings/general.general_webhook'),
                "icon" => "fab fa-hashtag",
                "placeholder" => trans('general.url'),
                "link" => "",
                "test" => "testWebhook"
            ),
            "google" => array(
                "name" => trans('admin/settings/general.google_workspaces'),
                "icon" => "fa-brands fa-google",
                "placeholder" => "https://chat.googleapis.com/v1/spaces/xxxxxxxx/messages?key=xxxxxx",
                "link" => "https://developers.google.com/chat/how-tos/webhooks#register_the_incoming_webhook",
                "test" => "googleWebhookTest"
            ),
            "microsoft" => array(
                "name" => trans('admin/settings/general.ms_teams'),
                "icon" => "fa-brands fa-microsoft",
                "placeholder" => "https://abcd.webhook.office.com/webhookb2/XXXXXXX",
                "link" => "https://support.microsoft.com/en-us/office/create-incoming-webhooks-with-workflows-for-microsoft-teams-8ae491c7-0394-4861-ba59-055e33f75498",
                "test" => "msTeamTestWebhook"
            ),
        ];

        $this->setting = Setting::getSettings();
        $this->save_button = trans('general.save');
        $this->webhook_selected = $this->setting->webhook_selected ?: 'slack';
        $this->webhook_name = $this->webhook_text[$this->setting->webhook_selected]["name"] ?? $this->webhook_text['slack']["name"];
        $this->webhook_icon = $this->webhook_text[$this->setting->webhook_selected]["icon"] ?? $this->webhook_text['slack']["icon"];
        $this->webhook_placeholder = $this->webhook_text[$this->setting->webhook_selected]["placeholder"] ?? $this->webhook_text['slack']["placeholder"];
        $this->webhook_link = $this->webhook_text[$this->setting->webhook_selected]["link"] ?? $this->webhook_text['slack']["link"];
        $this->webhook_test = $this->webhook_text[$this->setting->webhook_selected]["test"] ?? $this->webhook_text['slack']["test"];
        $this->webhook_endpoint = $this->setting->webhook_endpoint;
        $this->webhook_channel = $this->setting->webhook_channel;
        $this->webhook_botname = $this->setting->webhook_botname;
        $this->webhook_options = $this->setting->webhook_selected;
        $this->teams_webhook_deprecated = ! Str::contains((string) $this->webhook_endpoint, 'workflows');
        if($this->webhook_selected === 'microsoft' || $this->webhook_selected === 'google'){
            $this->webhook_channel = '#NA';
        }

        if($this->setting->webhook_endpoint != null && $this->setting->webhook_channel != null){
            $this->isDisabled= '';
        }
        if($this->webhook_selected === 'microsoft' && $this->teams_webhook_deprecated) {
            session()->flash('warning', trans('admin/settings/message.webhook.ms_teams_deprecation'));
        }
    }
    public function updated($field)
    {
        $this->validateOnly($field);
    }

    public function updatedWebhookSelected()
    {
        if (! array_key_exists($this->webhook_selected, $this->webhook_text)) {
            return;
        }

        $this->webhook_name = $this->webhook_text[$this->webhook_selected]['name'];
        $this->webhook_icon = $this->webhook_text[$this->webhook_selected]['icon'];
        $this->webhook_placeholder = $this->webhook_text[$this->webhook_selected]['placeholder'];
        $this->webhook_endpoint = null;
        $this->webhook_link = $this->webhook_text[$this->webhook_selected]['link'];
        $this->webhook_test = $this->webhook_text[$this->webhook_selected]['test'];
        if ($this->webhook_selected !== 'slack') {
            $this->isDisabled = '';
            $this->save_button = trans('general.save');
        }
        if ($this->webhook_selected === 'microsoft' || $this->webhook_selected === 'google') {
            $this->webhook_channel = '#NA';
        }
    }

    public function updatedWebhookEndpoint()
    {
        $this->teams_webhook_deprecated = ! Str::contains((string) $this->webhook_endpoint, 'workflows');
    }

    private function isButtonDisabled()
    {
        if (empty($this->webhook_endpoint) || empty($this->webhook_channel)) {
            $this->isDisabled = 'disabled';
            $this->save_button = trans('admin/settings/general.webhook_presave');
        }
    }

    public function render()
    {
        $this->isButtonDisabled();

        return view('livewire.slack-settings-form');

    }

    public function testWebhook()
    {
        if (! $this->guardWebhookEndpoint()) {
            return;
        }

        try {
            $response = $this->postWebhook([
                'channel' => $this->webhook_channel,
                'text' => trans('general.webhook_test_msg', ['app' => $this->webhook_name]),
                'username' => $this->webhook_botname,
                'icon_emoji' => ':heart:',
            ]);

            $this->handleWebhookResponse($response->status());
        } catch (\Throwable $exception) {
            $this->handleWebhookFailure($exception);
        }
    }


    public function clearSettings(){

        if (Helper::isDemoMode()) {
            session()->flash('error',trans('general.feature_disabled'));
        } else {
            $this->webhook_endpoint = '';
            $this->webhook_channel = '';
            $this->webhook_botname = '';
            $this->setting->webhook_endpoint = '';
            $this->setting->webhook_channel = '';
            $this->setting->webhook_botname = '';

            $this->setting->save();

            session()->flash('success', trans('admin/settings/message.update.success'));
        }
    }

    public function submit()
    {
        if (Helper::isDemoMode()) {
            session()->flash('error',trans('general.feature_disabled'));
        } else {
            $this->validate();

            $this->setting->webhook_selected = $this->webhook_selected;
            $this->setting->webhook_endpoint = $this->webhook_endpoint;
            $this->setting->webhook_channel = $this->webhook_channel;
            $this->setting->webhook_botname = $this->webhook_botname;

            $this->setting->save();

            session()->flash('success',trans('admin/settings/message.update.success'));
        }

    }
    public function googleWebhookTest()
    {
        if (! $this->guardWebhookEndpoint()) {
            return;
        }

        try {
            $response = $this->postWebhook([
                'text' => trans('general.webhook_test_msg', ['app' => $this->webhook_name]),
            ]);

            $this->handleWebhookResponse($response->status());
        } catch (\Throwable $exception) {
            $this->handleWebhookFailure($exception);
        }
    }

    public function msTeamTestWebhook()
    {
        if (! $this->guardWebhookEndpoint()) {
            return;
        }

        $message = trans('general.webhook_test_msg', ['app' => $this->webhook_name]);

        $payload = $this->teams_webhook_deprecated
            ? [
                '@type' => 'MessageCard',
                '@context' => 'http://schema.org/extensions',
                'summary' => trans('mail.snipe_webhook_summary', ['app' => config('app.name')]),
                'title' => trans('mail.snipe_webhook_test', ['app' => config('app.name')]),
                'text' => $message,
            ]
            : [
                'type' => 'message',
                'attachments' => [[
                    'contentType' => 'application/vnd.microsoft.card.adaptive',
                    'contentUrl' => null,
                    'content' => [
                        '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
                        'type' => 'AdaptiveCard',
                        'version' => '1.2',
                        'body' => [[
                            'type' => 'TextBlock',
                            'text' => $message,
                            'wrap' => true,
                        ]],
                    ],
                ]],
            ];

        try {
            $response = $this->postWebhook($payload);
            $this->handleWebhookResponse($response->status());
        } catch (\Throwable $exception) {
            $this->handleWebhookFailure($exception);
        }
    }

    private function guardWebhookEndpoint(): bool
    {
        $validator = Validator::make(
            ['webhook_endpoint' => $this->webhook_endpoint],
            ['webhook_endpoint' => ['required', 'url', 'max:2048', new ExternalUrl]],
        );

        if ($validator->passes()) {
            return true;
        }

        $this->disableSaveUntilRetested();
        session()->flash('error', $validator->errors()->first('webhook_endpoint'));

        return false;
    }

    private function postWebhook(array $payload): Response
    {
        return Http::asJson()
            ->connectTimeout(5)
            ->timeout(10)
            ->withOptions(['allow_redirects' => false])
            ->post((string) $this->webhook_endpoint, $payload)
            ->throw();
    }

    private function handleWebhookResponse(int $status): void
    {
        if ($status >= 300 && $status < 400) {
            $this->disableSaveUntilRetested();
            session()->flash('error', trans('admin/settings/message.webhook.error_redirect', [
                'endpoint' => $this->webhookOrigin(),
            ]));

            return;
        }

        $this->isDisabled = '';
        $this->save_button = trans('general.save');
        session()->flash('success', trans('admin/settings/message.webhook.success', [
            'webhook_name' => $this->webhook_name,
        ]));
    }

    private function handleWebhookFailure(\Throwable $exception): void
    {
        Log::warning('Webhook test failed', [
            'endpoint_origin' => $this->webhookOrigin(),
            'integration' => $this->webhook_selected,
            'exception' => $exception::class,
            'code' => $exception->getCode(),
        ]);

        $this->disableSaveUntilRetested();
        session()->flash('error', trans('admin/settings/message.webhook.error_safe', [
            'app' => $this->webhook_name,
        ]));
    }

    private function disableSaveUntilRetested(): void
    {
        $this->isDisabled = 'disabled';
        $this->save_button = trans('admin/settings/general.webhook_presave');
    }

    private function webhookOrigin(): string
    {
        $parts = parse_url((string) $this->webhook_endpoint);

        if ($parts === false || empty($parts['host'])) {
            return '[invalid endpoint]';
        }

        $scheme = isset($parts['scheme']) ? strtolower($parts['scheme']).'://' : '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return $scheme.$parts['host'].$port;
    }
}
