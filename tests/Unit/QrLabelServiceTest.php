<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\ComponentInstance;
use App\Models\Setting;
use App\Services\QrCodeService;
use App\Services\QrLabelService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;
use function Livewire\invade;

class QrLabelServiceTest extends TestCase
{
    public function test_path_generates_expected_filenames(): void
    {
        $asset = new Asset(['asset_tag' => 'My Asset Tag']);
        $service = new QrLabelService();

        $this->assertSame('labels/qr-v14-dymo-89x36-my-asset-tag.png', invade($service)->path($asset, 'png', 'dymo-89x36'));
        $this->assertSame('labels/qr-v14-dymo-89x36-my-asset-tag.pdf', invade($service)->path($asset, 'pdf', 'dymo-89x36'));
    }

    public function test_generate_creates_png_and_pdf_labels(): void
    {
        Storage::fake('public');
        $asset = Asset::factory()->create();
        $service = app(QrLabelService::class);

        $service->generate($asset, 'dymo-89x36');

        $slug = Str::slug($asset->asset_tag);
        Storage::disk('public')->assertExists("labels/qr-v14-dymo-89x36-{$slug}.png");
        Storage::disk('public')->assertExists("labels/qr-v14-dymo-89x36-{$slug}.pdf");
    }

    public function test_pdf_falls_back_to_print_date_when_name_missing(): void
    {
        Storage::fake('public');
        Carbon::setTestNow('2024-04-01');
        $asset = new Asset(['asset_tag' => 'FallbackTest']);
        $mock = Mockery::mock(QrCodeService::class);
        $mock->shouldReceive('png')->andReturn('png');
        if ($settings = Setting::getSettings()) {
            Setting::unguarded(fn() => $settings->update([
                'site_name' => 'Inbit',
                'qr_formats' => 'pdf',
            ]));
            Setting::$_cache = null;
        }
        $settings = Setting::getSettings() ?? (object) [];
        $companyName = trim((string) ($settings->site_name ?? '')) ?: 'Inbit';
        $expectedCaption = [
            'top' => [],
            'bottom' => [trans('admin/hardware/form.tag').': FALLBACKTEST'],
        ];
        $mock->shouldReceive('pdf')
            ->with('FALLBACKTEST', Mockery::any(), Mockery::any(), 'dymo-89x36', $expectedCaption)
            ->andReturn('pdf');
        app()->instance(QrCodeService::class, $mock);

        $service = app(QrLabelService::class);
        $service->generate($asset, 'dymo-89x36');

        $slug = Str::slug($asset->asset_tag);
        Storage::disk('public')->assertExists("labels/qr-v14-dymo-89x36-{$slug}.pdf");
        Carbon::setTestNow();
        Mockery::close();
    }

    public function test_component_instance_pdf_uses_stable_component_payload_and_caption(): void
    {
        $component = ComponentInstance::factory()->create([
            'component_tag' => 'INBIT-C-CP0001',
            'display_name' => 'Replacement SSD',
            'serial' => 'SN123',
            'qr_uid' => 'component-qr-uid',
        ]);
        $mock = Mockery::mock(QrCodeService::class);
        $expectedCaption = [
            'top' => [],
            'bottom' => [
                'Replacement SSD',
                __('Component tag').': INBIT-C-CP0001',
                trans('admin/hardware/form.serial').': SN123',
            ],
        ];

        $mock->shouldReceive('pdf')
            ->once()
            ->with('CMP:component-qr-uid', null, Mockery::any(), 'dymo-99010-89x28', $expectedCaption)
            ->andReturn('pdf');
        app()->instance(QrCodeService::class, $mock);

        $this->assertSame('pdf', app(QrLabelService::class)->pdfBinaryFor($component, 'dymo-99010-89x28'));
        Mockery::close();
    }
}
