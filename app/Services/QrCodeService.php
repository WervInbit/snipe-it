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
use Illuminate\Support\Str;

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
                ->margin((int) ($tpl['qr_margin'] ?? 0));

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
     *
     * @param array<string,array<int,string>>|string|null $caption
     */
    public function pdf(string $data, ?string $label = null, ?string $logoPath = null, ?string $template = null, array|string|null $caption = null): string
    {
        $tpl = $this->template($template);
        $png = $this->png($data, $label, $logoPath, $template);
        $fragment = $this->renderLabelFragment($png, $tpl, $caption, false);
        [$html, $paper] = $this->renderLabelDocument($tpl, [$fragment]);

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paper);
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Build the HTML fragment for a single label (used by both single/batch PDFs).
     *
     * @param array<string,array<int,string>>|string|null $caption
     */
    public function renderLabelFragment(string $pngData, array $tpl, array|string|null $caption = null, bool $pageBreak = false): string
    {
        $padding = (float) ($tpl['padding_mm'] ?? 1.2);
        $fontSize = (float) ($tpl['caption_font_size'] ?? 8);
        $lineHeight = (float) ($tpl['caption_line_height'] ?? 1.2);
        $maxLines = (int) ($tpl['caption_lines'] ?? 2);
        $wrapAt = (int) ($tpl['caption_wrap'] ?? 28);
        $maxChars = (int) ($tpl['caption_max_chars'] ?? 64);

        $bottomLines = [];
        if (is_array($caption)) {
            $bottomLines = array_values(array_filter($caption['bottom'] ?? [], fn ($line) => trim((string) $line) !== ''));
        } elseif (is_string($caption) && trim($caption) !== '') {
            $bottomLines = $this->captionLines($caption, $maxChars, $wrapAt, $maxLines);
        }

        $labelWidth = (float) $tpl['width_mm'];
        $labelHeight = (float) $tpl['height_mm'];
        $layout = $tpl['layout'] ?? null;
        $encoded = base64_encode($pngData);
        $textHtml = $this->renderTextLines($bottomLines);

        if ($layout === 'square-stack') {
            $qrBox = (float) ($tpl['qr_box_mm'] ?? 20.0);
            $textBand = (float) ($tpl['text_height_mm'] ?? 2.0);
            $textGap = (float) ($tpl['text_gap_mm'] ?? 0.0);
            $qrTop = $padding;
            $qrLeft = (float) ($tpl['qr_left_mm'] ?? $padding);
            $textLeft = (float) ($tpl['text_left_mm'] ?? $padding);
            $textRight = (float) ($tpl['text_right_mm'] ?? $textLeft);
            $textTop = $qrTop + $qrBox + $textGap;

            $containerStyles = sprintf(
                'position:relative;width:%smm;height:%smm;box-sizing:border-box;overflow:hidden;',
                number_format($labelWidth, 3, '.', ''),
                number_format($labelHeight, 3, '.', '')
            );
            $qrStyles = sprintf(
                'position:absolute;top:%smm;left:%smm;width:%smm;height:%smm;object-fit:contain;display:block;',
                number_format($qrTop, 3, '.', ''),
                number_format($qrLeft, 3, '.', ''),
                number_format($qrBox, 3, '.', ''),
                number_format($qrBox, 3, '.', '')
            );
            $textStyles = sprintf(
                'position:absolute;left:%smm;right:%smm;top:%smm;height:%smm;display:flex;flex-direction:row;align-items:center;justify-content:center;text-align:center;',
                number_format($textLeft, 3, '.', ''),
                number_format($textRight, 3, '.', ''),
                number_format($textTop, 3, '.', ''),
                number_format($textBand, 3, '.', '')
            );
        } else {
            $topMargin = max(0.0, $labelHeight * 0.05);
            $bottomMargin = $topMargin;
            $leftMargin = max(0.0, $labelWidth * 0.05);
            $rightMargin = $leftMargin;
            $gutter = max(1.0, $labelWidth * 0.02);
            $qrWidth = max(10.0, min($labelWidth * 0.45, (float) ($tpl['qr_column_mm'] ?? $labelWidth * 0.4)));
            $qrHeight = max(10.0, $labelHeight - $topMargin - $bottomMargin);
            $textLeft = $leftMargin + $qrWidth + $gutter;
            $textWidth = max(10.0, $labelWidth - $rightMargin - $textLeft);
            $textHeight = $qrHeight;

            $containerStyles = sprintf(
                'position:relative;width:%smm;height:%smm;',
                number_format($labelWidth, 3, '.', ''),
                number_format($labelHeight, 3, '.', '')
            );
            $qrStyles = sprintf(
                'position:absolute;top:%smm;left:%smm;width:%smm;height:%smm;display:block;object-fit:contain;',
                number_format($topMargin, 3, '.', ''),
                number_format($leftMargin, 3, '.', ''),
                number_format($qrWidth, 3, '.', ''),
                number_format($qrHeight, 3, '.', '')
            );
            $textStyles = sprintf(
                'position:absolute;top:%smm;right:%smm;width:%smm;height:%smm;display:flex;flex-direction:column;justify-content:flex-end;text-align:left;',
                number_format($topMargin, 3, '.', ''),
                number_format($rightMargin, 3, '.', ''),
                number_format($textWidth, 3, '.', ''),
                number_format($textHeight, 3, '.', '')
            );
        }

        return <<<HTML
<div class="qr-label" style="{$containerStyles}">
    <img src="data:image/png;base64,{$encoded}" alt="QR label" style="{$qrStyles}">
    <div class="qr-text" style="{$textStyles}">{$textHtml}</div>
</div>
HTML;
    }

    /**
     * Build the CSS used for label rendering in both PDF and UI previews.
     */
    public function labelStyles(array $tpl, bool $includePage = true): string
    {
        $width = (float) $tpl['width_mm'];
        $height = (float) $tpl['height_mm'];
        $fontSize = (float) ($tpl['caption_font_size'] ?? 8);
        $lineHeight = (float) ($tpl['caption_line_height'] ?? 1.2);

        $rules = [];
        if ($includePage) {
            $rules[] = "@page { margin: 0; size: {$width}mm {$height}mm; }";
            $rules[] = "html, body { margin: 0; padding: 0; width: {$width}mm; height: {$height}mm; }";
        }
        $rules[] = ".qr-label { width: {$width}mm; height: {$height}mm; box-sizing: border-box; overflow: hidden; position: relative; }";
        $rules[] = ".qr-text { font-size: {$fontSize}pt; line-height: {$lineHeight}; text-align: left; }";
        $rules[] = ".qr-text-line { display: block; font-size: {$fontSize}pt; font-weight: 600; }";

        return implode("\n", $rules);
    }

    /**
     * Wrap the provided fragments in a Dompdf-ready document.
     *
     * @param array<int, string> $fragments
     * @return array{0: string, 1: array<int, float>}
     */
    public function renderLabelDocument(array $tpl, array $fragments): array
    {
        $width = (float) $tpl['width_mm'];
        $height = (float) $tpl['height_mm'];
        $style = $this->labelStyles($tpl, true);

        $html = '<html><head><meta charset="UTF-8"><style>' . $style . '</style></head><body>' .
            implode('', $fragments) .
            '</body></html>';
        $paper = [0, 0, $width * 72 / 25.4, $height * 72 / 25.4];

        return [$html, $paper];
    }

    protected function template(?string $name): array
    {
        $templates = config('qr_templates.templates');
        $name = $name ?? config('qr_templates.default');
        return $templates[$name] ?? reset($templates);
    }

    /**
     * Break the caption into lines that fit on the label.
     *
     * @return array<int, string>
     */
    protected function captionLines(?string $caption, int $maxChars, int $wrapAt, int $maxLines): array
    {
        $caption = (string) $caption;
        if ($caption === '') {
            return [];
        }

        $wrapAt = max(1, $wrapAt);
        $maxLines = max(1, $maxLines);
        $maxChars = max(1, $maxChars);
        $rawLines = preg_split("/\r\n|\n|\r/", $caption) ?: [];
        $lines = [];

        foreach ($rawLines as $raw) {
            $normalized = preg_replace('/\s+/u', ' ', trim($raw));
            if ($normalized === '') {
                continue;
            }

            $wrapped = wordwrap($normalized, $wrapAt, "\n", true);
            foreach (explode("\n", $wrapped) as $chunk) {
                $chunk = trim($chunk);
                if ($chunk === '') {
                    continue;
                }
                $lines[] = Str::limit($chunk, $maxChars, '');
                if (count($lines) >= $maxLines) {
                    return array_slice($lines, 0, $maxLines);
                }
            }
        }

        return array_slice($lines, 0, $maxLines);
    }

    /**
     * @param array<int, string> $lines
     */
    protected function renderTextLines(array $lines): string
    {
        if (empty($lines)) {
            return '<div class="qr-text-line">&nbsp;</div>';
        }

        $class = 'qr-text-line';
        $escaped = array_map(fn ($line) => e(Str::limit($line, 64), false), $lines);

        return implode('', array_map(fn ($line) => '<div class="'.$class.'">'.$line.'</div>', $escaped));
    }
}
