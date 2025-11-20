<?php

namespace Tests\Unit;

use App\Models\Asset;
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

        $this->assertSame('labels/qr-v4-dymo-89x36-my-asset-tag.png', invade($service)->path($asset, 'png', 'dymo-89x36'));
        $this->assertSame('labels/qr-v4-dymo-89x36-my-asset-tag.pdf', invade($service)->path($asset, 'pdf', 'dymo-89x36'));
    }

    public function test_generate_creates_png_and_pdf_labels(): void
    {
        Storage::fake('public');
        $asset = Asset::factory()->create();
        $service = app(QrLabelService::class);

        $service->generate($asset, 'dymo-89x36');

        $slug = Str::slug($asset->asset_tag);
        Storage::disk('public')->assertExists("labels/qr-v4-dymo-89x36-{$slug}.png");
        Storage::disk('public')->assertExists("labels/qr-v4-dymo-89x36-{$slug}.pdf");
    }

    public function test_pdf_falls_back_to_print_date_when_name_missing(): void
    {
        Storage::fake('public');
        Carbon::setTestNow('2024-04-01');
        $asset = new Asset(['asset_tag' => 'FallbackTest']);
        $mock = Mockery::mock(QrCodeService::class);
        $mock->shouldReceive('png')->andReturn('png');
        if ($settings = Setting::getSettings()) {
            Setting::unguarded(fn() => $settings->update(['site_name' => 'Inbit']));
            Setting::$_cache = null;
        }
        $settings = Setting::getSettings() ?? (object) [];
        $companyName = trim((string) ($settings->site_name ?? '')) ?: 'Inbit';
        $expectedCaption = [
            'top' => [],
            'bottom' => [trans('admin/hardware/form.tag').': FallbackTest'],
        ];
        $mock->shouldReceive('pdf')
            ->with('FallbackTest', Mockery::any(), Mockery::any(), 'dymo-89x36', $expectedCaption)
            ->andReturn('pdf');
        app()->instance(QrCodeService::class, $mock);

        $service = app(QrLabelService::class);
        $service->generate($asset, 'dymo-89x36');

        $slug = Str::slug($asset->asset_tag);
        Storage::disk('public')->assertExists("labels/qr-v4-dymo-89x36-{$slug}.pdf");
        Carbon::setTestNow();
        Mockery::close();
    }
}
