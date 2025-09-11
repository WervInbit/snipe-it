<?php

namespace Tests\Unit;

use App\Models\Asset;
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

        $this->assertSame('labels/qr-v3-dymo-89x36-my-asset-tag.png', invade($service)->path($asset, 'png', 'dymo-89x36'));
        $this->assertSame('labels/qr-v3-dymo-89x36-my-asset-tag.pdf', invade($service)->path($asset, 'pdf', 'dymo-89x36'));
    }

    public function test_generate_creates_png_and_pdf_labels(): void
    {
        Storage::fake('public');
        $asset = Asset::factory()->create();
        $service = app(QrLabelService::class);

        $service->generate($asset, 'dymo-89x36');

        $slug = Str::slug($asset->asset_tag);
        Storage::disk('public')->assertExists("labels/qr-v3-dymo-89x36-{$slug}.png");
        Storage::disk('public')->assertExists("labels/qr-v3-dymo-89x36-{$slug}.pdf");
    }

    public function test_generate_supports_alternate_template(): void
    {
        Storage::fake('public');
        $asset = Asset::factory()->create();
        $service = app(QrLabelService::class);

        $service->generate($asset, 'dymo-54x25');

        $slug = Str::slug($asset->asset_tag);
        Storage::disk('public')->assertExists("labels/qr-v3-dymo-54x25-{$slug}.png");
        Storage::disk('public')->assertExists("labels/qr-v3-dymo-54x25-{$slug}.pdf");
    }

    public function test_pdf_falls_back_to_print_date_when_name_missing(): void
    {
        Storage::fake('public');
        Carbon::setTestNow('2024-04-01');
        $asset = new Asset(['asset_tag' => 'FallbackTest']);
        $mock = Mockery::mock(QrCodeService::class);
        $mock->shouldReceive('png')->andReturn('png');
        $mock->shouldReceive('pdf')
            ->with('FallbackTest', 'FallbackTest', Mockery::any(), 'dymo-89x36', 'QR printed – 2024-04-01')
            ->andReturn('pdf');
        app()->instance(QrCodeService::class, $mock);

        $service = app(QrLabelService::class);
        $service->generate($asset, 'dymo-89x36');

        $slug = Str::slug($asset->asset_tag);
        Storage::disk('public')->assertExists("labels/qr-v3-dymo-89x36-{$slug}.pdf");
        Carbon::setTestNow();
        Mockery::close();
    }
}
