<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Label\Alignment\LabelAlignmentCenter;
use Endroid\QrCode\Label\Font\NotoSans;
use Dompdf\Dompdf;
use Endroid\QrCode\Writer\PngWriter;
use BaconQrCode\Renderer\Image\GdImageBackEnd;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer as BaconWriter;
use Com\Tecnick\Barcode\Barcode;

class QrCodeService
{
    /**
     * Render a QR code as PNG binary data.
     */
    public function png(string $data, ?string $label = null, ?string $logoPath = null, ?string $template = null): string
    {
        $tpl = $this->template($template);
        // Prefer Endroid if available; otherwise fall back to BaconQrCode (GD backend)
        if (class_exists(\Endroid\QrCode\Builder\Builder::class)) {
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

        // BaconQrCode fallback if available (prefer GD, then Imagick). Logo/label applied manually.
        $size = (int) ($tpl['qr_size'] ?? 256);
        $gdAvailable = function_exists('imagecreatetruecolor');
        if (class_exists(\BaconQrCode\Writer::class)) {
            $backend = null;
            if (class_exists(\BaconQrCode\Renderer\Image\GdImageBackEnd::class) && $gdAvailable) {
                $backend = new GdImageBackEnd();
            } elseif (
                class_exists(\BaconQrCode\Renderer\Image\ImagickImageBackEnd::class)
                && extension_loaded('imagick')
            ) {
                $backend = new ImagickImageBackEnd();
            }

            if ($backend) {
                $renderer = new ImageRenderer(
                    new RendererStyle($size, 0),
                    $backend
                );
                $writer = new BaconWriter($renderer);
                $png = $writer->writeString($data);
            } else {
                $png = '';
            }
        } else {
            $png = '';
        }

        // If BaconQrCode not usable, try tc-lib-barcode (always required by this app)
        if ($png === '' && class_exists(\Com\Tecnick\Barcode\Barcode::class)) {
            $barcode = new Barcode();
            // Generate QR with high error correction; module size chosen for quality
            $module = max(2, (int) round(($size ?: 256) / 64));
            $bobj = $barcode->getBarcodeObj('QRCODE,H', (string) $data, $module, $module, 'black', [0, 0, 0, 0]);
            $bobj->setBackgroundColor('#ffffff');
            $raw = $bobj->getPngData();
            if ($gdAvailable) {
                // Scale to requested size using GD if available
                $src = @imagecreatefromstring($raw);
                if ($src) {
                    $w = imagesx($src); $h = imagesy($src);
                    $dst = imagecreatetruecolor($size, $size);
                    $white = imagecolorallocate($dst, 255, 255, 255);
                    imagefilledrectangle($dst, 0, 0, $size, $size, $white);
                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $size, $size, $w, $h);
                    ob_start(); imagepng($dst); $png = (string) ob_get_clean();
                    imagedestroy($src); imagedestroy($dst);
                } else {
                    $png = $raw;
                }
            } else {
                // No GD: return raw PNG data
                $png = $raw;
            }
        }

        // If no label and no logo requested, return the rendered QR as-is
        if (! $label && ! $logoPath && $png !== '') {
            return $png;
        }

        // Compose label/logo using GD
        if (! $gdAvailable) {
            // GD not available: return base PNG without overlays
            return $png;
        }
        $qrImg = $png !== '' ? imagecreatefromstring($png) : null;
        if (! $qrImg) {
            // As a last resort, just return the raw (possibly empty) PNG
            return $png;
        }

        $qrW = imagesx($qrImg);
        $qrH = imagesy($qrImg);

        // Determine extra height for label (basic GD font rendering)
        $font = 3; // built-in GD font
        $labelHeight = $label ? (imagefontheight($font) + 6) : 0;

        // Create final canvas (white background)
        $finalImg = imagecreatetruecolor($qrW, $qrH + $labelHeight);
        $white = imagecolorallocate($finalImg, 255, 255, 255);
        imagefilledrectangle($finalImg, 0, 0, $qrW, $qrH + $labelHeight, $white);

        // Copy QR onto canvas
        imagecopy($finalImg, $qrImg, 0, 0, 0, 0, $qrW, $qrH);

        // Overlay logo at center if provided
        if ($logoPath && file_exists($logoPath)) {
            $logoData = @file_get_contents($logoPath);
            if ($logoData !== false) {
                $logoImg = @imagecreatefromstring($logoData);
                if ($logoImg) {
                    $targetW = min(90, (int) round($qrW * 0.33));
                    $scale = $targetW / max(1, imagesx($logoImg));
                    $targetH = (int) round(imagesy($logoImg) * $scale);
                    $resized = imagecreatetruecolor($targetW, $targetH);
                    // Preserve transparency
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                    imagecopyresampled(
                        $resized,
                        $logoImg,
                        0,
                        0,
                        0,
                        0,
                        $targetW,
                        $targetH,
                        imagesx($logoImg),
                        imagesy($logoImg)
                    );
                    $dstX = (int) round(($qrW - $targetW) / 2);
                    $dstY = (int) round(($qrH - $targetH) / 2);
                    imagecopy($finalImg, $resized, $dstX, $dstY, 0, 0, $targetW, $targetH);
                    imagedestroy($resized);
                    imagedestroy($logoImg);
                }
            }
        }

        // Render label (simple black text centered)
        if ($label) {
            $black = imagecolorallocate($finalImg, 0, 0, 0);
            $textW = imagefontwidth($font) * strlen($label);
            $textX = max(0, (int) floor(($qrW - $textW) / 2));
            $textY = $qrH + 3; // small top padding
            imagestring($finalImg, $font, $textX, $textY, $label, $black);
        }

        ob_start();
        imagepng($finalImg);
        $out = (string) ob_get_clean();
        imagedestroy($qrImg);
        imagedestroy($finalImg);
        return $out;
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
