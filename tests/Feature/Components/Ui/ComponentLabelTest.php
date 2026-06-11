<?php

namespace Tests\Feature\Components\Ui;

use App\Models\ComponentInstance;
use App\Models\Setting;
use App\Models\User;
use App\Services\QrLabelService;
use Mockery;
use Tests\TestCase;

class ComponentLabelTest extends TestCase
{
    public function testComponentDetailPageShowsQrDownloadAndPrintControls(): void
    {
        $settings = Setting::getSettings();
        if ($settings) {
            Setting::unguarded(fn () => $settings->update(['qr_formats' => 'png,pdf,qr']));
            Setting::$_cache = null;
        }

        config(['qr_templates.queues' => ['front-desk-labels']]);

        $component = ComponentInstance::factory()->create([
            'display_name' => 'Replacement SSD',
        ]);

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('components.show', $component));

        $response->assertOk();
        $response->assertSee('data-testid="component-qr-action-panel"', false);
        $response->assertSee('id="component-template-picker"', false);
        $response->assertSee('id="component-printer-picker"', false);
        $response->assertSee('front-desk-labels');
        $response->assertSee(route('components.print-label', $component), false);
        $response->assertSee('components/'.$component->id.'/qr-label?template=dymo-s0929120-25x25', false);
        $response->assertSee(trans('general.download_qr_label'));
    }

    public function testComponentQrLabelCanBeDownloaded(): void
    {
        $component = ComponentInstance::factory()->create([
            'component_tag' => 'INBIT-CP0001',
        ]);
        $labels = Mockery::mock(QrLabelService::class);
        $labels->shouldReceive('pngBinaryFor')
            ->once()
            ->with(Mockery::on(fn ($target) => $target instanceof ComponentInstance && $target->is($component)), 'dymo-s0929120-25x25')
            ->andReturn('fake-png');
        app()->instance(QrLabelService::class, $labels);

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('components.qr-label.download', [
                'component_id' => $component,
                'template' => 'dymo-s0929120-25x25',
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'image/png');
        $this->assertSame('fake-png', $response->getContent());
        $response->assertHeader('content-disposition', 'attachment; filename="qr-label-inbit-cp0001.png"');
        Mockery::close();
    }

    public function testComponentPrintRequiresConfiguredPrinterQueue(): void
    {
        config(['qr_templates.queues' => [], 'qr_templates.print_queue' => null]);

        $component = ComponentInstance::factory()->create();
        $token = 'component-label-print-token';

        $this->actingAs(User::factory()->superuser()->create())
            ->withSession(['_token' => $token])
            ->postJson(route('components.print-label', $component), [
                '_token' => $token,
                'template' => 'dymo-s0929120-25x25',
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Label printer queue is not configured. Set LABEL_PRINTER_QUEUE.',
            ]);
    }
}
