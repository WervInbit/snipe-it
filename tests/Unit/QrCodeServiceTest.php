<?php

namespace Tests\Unit;

use App\Services\QrCodeService;
use Tests\TestCase;

class QrCodeServiceTest extends TestCase
{
    public function test_it_generates_png_and_pdf(): void
    {
        $service = app(QrCodeService::class);
        $data = 'ASSET-123';

        $png = $service->png($data, $data, null, 'dymo-89x36');
        $pdf = $service->pdf($data, $data, null, 'dymo-89x36');

        $this->assertNotEmpty($png);
        $this->assertNotEmpty($pdf);
    }

    public function test_pdf_can_include_caption(): void
    {
        $service = app(QrCodeService::class);
        $data = 'ASSET-123';
        $pdf = $service->pdf($data, $data, null, 'dymo-89x36', 'My Asset');
        $this->assertNotEmpty($pdf);
    }
}
