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

        $png = $service->png($data, $data);
        $pdf = $service->pdf($data, $data);

        $this->assertNotEmpty($png);
        $this->assertNotEmpty($pdf);
    }
}
