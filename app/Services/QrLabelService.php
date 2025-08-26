<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Setting;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use TCPDF;

class QrLabelService
{
    protected string $directory = 'labels';

    /**
     * Generate PNG and PDF labels for an asset.
     */
    public function generate(Asset $asset): void
    {
        $settings = Setting::getSettings();
        $disk = Storage::disk('public');
        $disk->makeDirectory($this->directory);

        // build QR image
        $renderer = new GDLibRenderer(300);
        $writer = new Writer($renderer);
        $qrData = $writer->writeString(route('hardware.show', $asset));
        $qrImg = imagecreatefromstring($qrData);
        $width = imagesx($qrImg);
        $height = imagesy($qrImg);

        // canvas with space for text
        $labelHeight = $height + 20;
        $canvas = imagecreatetruecolor($width, $labelHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $qrImg, 0, 0, 0, 0, $width, $height);
        imagedestroy($qrImg);

        // optional logo
        if ($settings->label_logo && $disk->exists($settings->label_logo)) {
            $logo = imagecreatefromstring($disk->get($settings->label_logo));
            $logoW = imagesx($logo);
            $logoH = imagesy($logo);
            $destW = $width * 0.3;
            $destH = $logoH * ($destW / $logoW);
            imagecopyresampled($canvas, $logo, ($width - $destW) / 2, ($height - $destH) / 2, 0, 0, $destW, $destH, $logoW, $logoH);
            imagedestroy($logo);
        }

        // asset tag text
        $font = 5;
        $text = $asset->asset_tag;
        $textW = imagefontwidth($font) * strlen($text);
        $black = imagecolorallocate($canvas, 0, 0, 0);
        imagestring($canvas, $font, ($width - $textW) / 2, $height + 2, $text, $black);

        ob_start();
        imagepng($canvas);
        $pngData = ob_get_clean();
        imagedestroy($canvas);

        $disk->put($this->path($asset, 'png'), $pngData);

        // PDF
        $pdf = new TCPDF();
        $pdf->AddPage();
        $tmp = tempnam(sys_get_temp_dir(), 'qr');
        file_put_contents($tmp, $pngData);
        $pdf->Image($tmp, 15, 15, 50, 50, 'PNG');
        unlink($tmp);
        $pdf->SetY(70);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 0, $text, 0, 1, 'C');
        $disk->put($this->path($asset, 'pdf'), $pdf->Output('', 'S'));
    }

    /**
     * Return URL for label in desired format.
     */
    public function url(Asset $asset, string $format = 'png'): string
    {
        $disk = Storage::disk('public');
        $file = $this->path($asset, $format);
        if (! $disk->exists($file)) {
            $this->generate($asset);
        }
        return $disk->url($file);
    }

    protected function path(Asset $asset, string $format): string
    {
        return $this->directory.'/qr-'.Str::slug($asset->asset_tag).'.'.$format;
    }
}

