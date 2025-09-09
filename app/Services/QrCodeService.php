<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Label\Alignment\LabelAlignmentCenter;
use Endroid\QrCode\Label\Font\NotoSans;
use Dompdf\Dompdf;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeService
{
    /**
     * Render a QR code as PNG binary data.
     */
    public function png(string $data, ?string $label = null, ?string $logoPath = null, ?string $template = null): string
    {
        $tpl = $this->template($template);

        $builder = Builder::create()
            ->writer(new PngWriter())
            ->data($data)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size($tpl['qr_size'])
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
    public function pdf(string $data, ?string $label = null, ?string $logoPath = null, ?string $template = null, ?string $caption = null): string
    {
        $tpl = $this->template($template);

        $png = $this->png($data, $label, $logoPath, $template);

        $dompdf = new Dompdf();
        $width = $tpl['width_mm'] * 72 / 25.4;
        $height = $tpl['height_mm'] * 72 / 25.4;
        $captionHtml = $caption ? '<div style="margin-top:2px;font-size:10px;text-align:center;">' . htmlspecialchars($caption, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>' : '';
        $html = '<html><body style="margin:0;padding:0;"><div style="width:' . $tpl['width_mm'] . 'mm;height:' . $tpl['height_mm'] . 'mm;display:flex;flex-direction:column;justify-content:center;align-items:center;"><img src="data:image/png;base64,' . base64_encode($png) . '" style="max-height:80%;max-width:100%;" />' . $captionHtml . '</div></body></html>';
        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, $width, $height]);
        $dompdf->render();

        return $dompdf->output();
    }

    protected function template(?string $name): array
    {
        $templates = config('qr_templates.templates');
        $name = $name ?? config('qr_templates.default');
        return $templates[$name] ?? reset($templates);
    }
}
