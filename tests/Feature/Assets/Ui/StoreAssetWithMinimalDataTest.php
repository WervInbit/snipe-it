<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\User;
use App\Services\QrLabelPrintService;
use App\Services\QrLabelService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreAssetWithMinimalDataTest extends TestCase
{
    #[Test]
    public function assetCanBeCreatedWithMinimalData()
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->post(route('hardware.store'), [])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('qr_pdf')
            ->assertSessionMissing('qr_png');

        $this->assertEquals(1, Asset::count());
        $asset = Asset::first();
        $response->assertRedirect(route('hardware.created', $asset));
        $this->actingAs($admin)
            ->get(route('hardware.created', $asset))
            ->assertOk()
            ->assertSee('data-testid="asset-creation-result"', false)
            ->assertSee(route('hardware.show', $asset))
            ->assertSee(route('hardware.print-label', $asset), false)
            ->assertSee('data-template="'.config('qr_templates.default').'"', false)
            ->assertSee(trans('general.print_qr'));

        $this->assertMatchesRegularExpression('/^INBIT-[A-Z]{2}\d{4}$/', $asset->asset_tag);
        $this->assertNull($asset->model_id);
        $this->assertNotNull($asset->status_id);
        $this->assertTrue((bool) $asset->assetstatus->default_label);
        $this->assertFalse($asset->is_sellable);
    }

    #[Test]
    public function postCreationPrintActionDispatchesConfiguredPrinterJob(): void
    {
        config([
            'qr_templates.queues' => ['dymo25'],
            'qr_templates.print_queue' => 'dymo25',
        ]);

        $admin = User::factory()->admin()->create();
        $asset = Asset::factory()->create();
        $template = config('qr_templates.default');

        $labels = Mockery::mock(QrLabelService::class);
        $labels->shouldReceive('pdfBinaryFor')
            ->once()
            ->with(Mockery::on(fn ($target) => $target instanceof Asset && $target->is($asset)), $template)
            ->andReturn('print-ready-pdf');
        app()->instance(QrLabelService::class, $labels);

        $printer = Mockery::mock(QrLabelPrintService::class);
        $printer->shouldReceive('templates')->once()->andReturn(config('qr_templates.templates'));
        $printer->shouldReceive('queues')->once()->andReturn(['dymo25']);
        $printer->shouldReceive('resolveQueue')->once()->with(null)->andReturn('dymo25');
        $printer->shouldReceive('printPdf')
            ->once()
            ->with('print-ready-pdf', 'dymo25')
            ->andReturn([
                'successful' => true,
                'output' => 'request id is dymo25-42',
                'error_output' => '',
                'job_id' => 'dymo25-42',
            ]);
        app()->instance(QrLabelPrintService::class, $printer);

        $this->actingAs($admin)
            ->postJson(route('hardware.print-label', $asset), [
                'template' => $template,
            ])
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
                'queue' => 'dymo25',
                'job_id' => 'dymo25-42',
            ]);
    }
}
