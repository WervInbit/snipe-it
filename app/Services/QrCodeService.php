<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Label\Alignment\LabelAlignmentCenter;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\Writer\PdfWriter;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeService
{
    /**
     * Render a QR code as PNG binary data.
     */
    public function png(string $data, ?string $label = null, ?string $logoPath = null): string
    {
        $builder = Builder::create()
            ->writer(new PngWriter())
            ->data($data)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size(300)
            ->margin(0);

        if ($logoPath) {
            $builder->logoPath($logoPath)->logoResizeToWidth(90);
        }

        if ($label) {
            $builder->labelText($label)
                ->labelFont(new NotoSans(16))
                ->labelAlignment(new LabelAlignmentCenter());
        }

        return $builder->build()->getString();
    }

    /**
     * Render a QR code as PDF binary data.
     */
    public function pdf(string $data, ?string $label = null, ?string $logoPath = null): string
    {
        $builder = Builder::create()
            ->writer(new PdfWriter())
            ->data($data)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size(300)
            ->margin(0);

        if ($logoPath) {
            $builder->logoPath($logoPath)->logoResizeToWidth(90);
        }

        if ($label) {
            $builder->labelText($label)
                ->labelFont(new NotoSans(16))
                ->labelAlignment(new LabelAlignmentCenter());
        }

        return $builder->build()->getString();
    }
}
