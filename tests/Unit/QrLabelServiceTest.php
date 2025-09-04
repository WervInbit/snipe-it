<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Services\QrLabelService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use function Livewire\invade;

class QrLabelServiceTest extends TestCase
{
    public function test_path_generates_expected_filenames(): void
    {
        $asset = new Asset(['asset_tag' => 'My Asset Tag']);
        $service = new QrLabelService();

        $this->assertSame('labels/qr-v2-my-asset-tag.png', invade($service)->path($asset, 'png'));
        $this->assertSame('labels/qr-v2-my-asset-tag.pdf', invade($service)->path($asset, 'pdf'));
    }

    public function test_generate_creates_png_and_pdf_labels(): void
    {
        Storage::fake('public');
        $asset = Asset::factory()->create();
        $service = app(QrLabelService::class);

        $service->generate($asset);

        $slug = Str::slug($asset->asset_tag);
        Storage::disk('public')->assertExists("labels/qr-v2-{$slug}.png");
        Storage::disk('public')->assertExists("labels/qr-v2-{$slug}.pdf");
    }
}
