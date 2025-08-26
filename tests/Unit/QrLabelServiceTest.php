<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Services\QrLabelService;
use Tests\TestCase;
use function Livewire\invade;

class QrLabelServiceTest extends TestCase
{
    public function test_path_generates_expected_filenames(): void
    {
        $asset = new Asset(['asset_tag' => 'My Asset Tag']);
        $service = new QrLabelService();

        $this->assertSame('labels/qr-my-asset-tag.png', invade($service)->path($asset, 'png'));
        $this->assertSame('labels/qr-my-asset-tag.pdf', invade($service)->path($asset, 'pdf'));
    }
}
