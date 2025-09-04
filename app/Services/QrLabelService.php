<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Accessory;
use App\Models\Component;
use App\Models\Consumable;
use App\Models\Setting;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use TCPDF;

class QrLabelService
{
    protected string $directory = 'labels';
    // Increment to invalidate previously generated image filenames
    protected string $version = 'v2';

    /**
     * Generate PNG and PDF labels for an asset.
     */
    public function generate(Asset $asset): void
    {
        $settings = Setting::getSettings();
        $disk = Storage::disk('public');
        $disk->makeDirectory($this->directory);

        // Build payload for in-app scanning only (no external URL)
        // Ensure the asset has a stable opaque identifier
        if (empty($asset->qr_uid)) {
            $asset->qr_uid = (string) Str::uuid();
            $asset->save();
        }
        $payload = 'A:'.$asset->qr_uid; // Prefix A denotes Asset

        // build QR image
        $renderer = new GDLibRenderer(300);
        $writer = new Writer($renderer);
        $qrData = $writer->writeString($payload);
        $qrImg = imagecreatefromstring($qrData);
        $width = imagesx($qrImg);
        $height = imagesy($qrImg);

        // canvas with space for text if enabled
        $labelHeight = $height + ($settings->qr_text_redundancy ? 20 : 0);
        $canvas = imagecreatetruecolor($width, $labelHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $qrImg, 0, 0, 0, 0, $width, $height);
        imagedestroy($qrImg);

        // optional logo
        $logoPath = $settings->qr_logo ?: $settings->label_logo;
        if ($logoPath && $disk->exists($logoPath)) {
            $logo = imagecreatefromstring($disk->get($logoPath));
            $logoW = imagesx($logo);
            $logoH = imagesy($logo);
            $destW = $width * 0.3;
            $destH = $logoH * ($destW / $logoW);
            imagecopyresampled($canvas, $logo, ($width - $destW) / 2, ($height - $destH) / 2, 0, 0, $destW, $destH, $logoW, $logoH);
            imagedestroy($logo);
        }
        // asset tag text
        $text = $asset->asset_tag;
        if ($settings->qr_text_redundancy) {
            $font = 5;
            $textW = imagefontwidth($font) * strlen($text);
            $black = imagecolorallocate($canvas, 0, 0, 0);
            imagestring($canvas, $font, ($width - $textW) / 2, $height + 2, $text, $black);
        }

        ob_start();
        imagepng($canvas);
        $pngData = ob_get_clean();
        imagedestroy($canvas);

        $formats = array_map('trim', explode(',', $settings->qr_formats ?? 'png,pdf'));

        if (in_array('png', $formats)) {
            $disk->put($this->path($asset, 'png'), $pngData);
        }

        if (in_array('pdf', $formats)) {
            $pdf = new TCPDF();
            $pdf->AddPage();
            $tmp = tempnam(sys_get_temp_dir(), 'qr');
            file_put_contents($tmp, $pngData);
            $pdf->Image($tmp, 15, 15, 50, 50, 'PNG');
            unlink($tmp);
            if ($settings->qr_text_redundancy) {
                $pdf->SetY(70);
                $pdf->SetFont('helvetica', '', 12);
                $pdf->Cell(0, 0, $text, 0, 1, 'C');
            }
            $disk->put($this->path($asset, 'pdf'), $pdf->Output('', 'S'));
        }
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
        return $this->directory.'/qr-'.$this->version.'-'.Str::slug($asset->asset_tag).'.'.$format;
    }

    /**
     * Generate labels for an accessory.
     */
    public function generateForAccessory(Accessory $item): void
    {
        $settings = Setting::getSettings();
        $disk = Storage::disk('public');
        $disk->makeDirectory($this->directory);

        if (empty($item->qr_uid)) {
            $item->qr_uid = (string) Str::uuid();
            $item->save();
        }
        $payload = 'ACC:'.$item->qr_uid;

        $renderer = new GDLibRenderer(300);
        $writer = new Writer($renderer);
        $qrData = $writer->writeString($payload);
        $qrImg = imagecreatefromstring($qrData);
        $width = imagesx($qrImg);
        $height = imagesy($qrImg);

        $labelHeight = $height + ($settings->qr_text_redundancy ? 20 : 0);
        $canvas = imagecreatetruecolor($width, $labelHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $qrImg, 0, 0, 0, 0, $width, $height);
        imagedestroy($qrImg);

        $logoPath = $settings->qr_logo ?: $settings->label_logo;
        if ($logoPath && $disk->exists($logoPath)) {
            $logo = imagecreatefromstring($disk->get($logoPath));
            $logoW = imagesx($logo);
            $logoH = imagesy($logo);
            $destW = $width * 0.3;
            $destH = $logoH * ($destW / $logoW);
            imagecopyresampled($canvas, $logo, ($width - $destW) / 2, ($height - $destH) / 2, 0, 0, $destW, $destH, $logoW, $logoH);
            imagedestroy($logo);
        }

        $text = $item->name ?? (string) $item->id;
        if ($settings->qr_text_redundancy) {
            $font = 5;
            $textW = imagefontwidth($font) * strlen($text);
            $black = imagecolorallocate($canvas, 0, 0, 0);
            imagestring($canvas, $font, ($width - $textW) / 2, $height + 2, $text, $black);
        }

        ob_start();
        imagepng($canvas);
        $pngData = ob_get_clean();
        imagedestroy($canvas);

        $formats = array_map('trim', explode(',', $settings->qr_formats ?? 'png,pdf'));
        $base = $this->directory.'/qr-ACC-'.($item->name ? Str::slug($item->name) : $item->id);
        if (in_array('png', $formats)) {
            $disk->put($base.'.png', $pngData);
        }
        if (in_array('pdf', $formats)) {
            $pdf = new TCPDF();
            $pdf->AddPage();
            $tmp = tempnam(sys_get_temp_dir(), 'qr');
            file_put_contents($tmp, $pngData);
            $pdf->Image($tmp, 15, 15, 50, 50, 'PNG');
            unlink($tmp);
            if ($settings->qr_text_redundancy) {
                $pdf->SetY(70);
                $pdf->SetFont('helvetica', '', 12);
                $pdf->Cell(0, 0, $text, 0, 1, 'C');
            }
            $disk->put($base.'.pdf', $pdf->Output('', 'S'));
        }
    }

    /**
     * Generate labels for a component.
     */
    public function generateForComponent(Component $item): void
    {
        $settings = Setting::getSettings();
        $disk = Storage::disk('public');
        $disk->makeDirectory($this->directory);

        if (empty($item->qr_uid)) {
            $item->qr_uid = (string) Str::uuid();
            $item->save();
        }
        $payload = 'CMP:'.$item->qr_uid;

        $renderer = new GDLibRenderer(300);
        $writer = new Writer($renderer);
        $qrData = $writer->writeString($payload);
        $qrImg = imagecreatefromstring($qrData);
        $width = imagesx($qrImg);
        $height = imagesy($qrImg);

        $labelHeight = $height + ($settings->qr_text_redundancy ? 20 : 0);
        $canvas = imagecreatetruecolor($width, $labelHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $qrImg, 0, 0, 0, 0, $width, $height);
        imagedestroy($qrImg);

        $logoPath = $settings->qr_logo ?: $settings->label_logo;
        if ($logoPath && $disk->exists($logoPath)) {
            $logo = imagecreatefromstring($disk->get($logoPath));
            $logoW = imagesx($logo);
            $logoH = imagesy($logo);
            $destW = $width * 0.3;
            $destH = $logoH * ($destW / $logoW);
            imagecopyresampled($canvas, $logo, ($width - $destW) / 2, ($height - $destH) / 2, 0, 0, $destW, $destH, $logoW, $logoH);
            imagedestroy($logo);
        }

        $text = $item->name ?? (string) $item->id;
        if ($settings->qr_text_redundancy) {
            $font = 5;
            $textW = imagefontwidth($font) * strlen($text);
            $black = imagecolorallocate($canvas, 0, 0, 0);
            imagestring($canvas, $font, ($width - $textW) / 2, $height + 2, $text, $black);
        }

        ob_start();
        imagepng($canvas);
        $pngData = ob_get_clean();
        imagedestroy($canvas);

        $formats = array_map('trim', explode(',', $settings->qr_formats ?? 'png,pdf'));
        $base = $this->directory.'/qr-CMP-'.($item->name ? Str::slug($item->name) : $item->id);
        if (in_array('png', $formats)) {
            $disk->put($base.'.png', $pngData);
        }
        if (in_array('pdf', $formats)) {
            $pdf = new TCPDF();
            $pdf->AddPage();
            $tmp = tempnam(sys_get_temp_dir(), 'qr');
            file_put_contents($tmp, $pngData);
            $pdf->Image($tmp, 15, 15, 50, 50, 'PNG');
            unlink($tmp);
            if ($settings->qr_text_redundancy) {
                $pdf->SetY(70);
                $pdf->SetFont('helvetica', '', 12);
                $pdf->Cell(0, 0, $text, 0, 1, 'C');
            }
            $disk->put($base.'.pdf', $pdf->Output('', 'S'));
        }
    }

    /**
     * Generate labels for a consumable.
     */
    public function generateForConsumable(Consumable $item): void
    {
        $settings = Setting::getSettings();
        $disk = Storage::disk('public');
        $disk->makeDirectory($this->directory);

        if (empty($item->qr_uid)) {
            $item->qr_uid = (string) Str::uuid();
            $item->save();
        }
        $payload = 'CON:'.$item->qr_uid;

        $renderer = new GDLibRenderer(300);
        $writer = new Writer($renderer);
        $qrData = $writer->writeString($payload);
        $qrImg = imagecreatefromstring($qrData);
        $width = imagesx($qrImg);
        $height = imagesy($qrImg);

        $labelHeight = $height + ($settings->qr_text_redundancy ? 20 : 0);
        $canvas = imagecreatetruecolor($width, $labelHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $qrImg, 0, 0, 0, 0, $width, $height);
        imagedestroy($qrImg);

        $logoPath = $settings->qr_logo ?: $settings->label_logo;
        if ($logoPath && $disk->exists($logoPath)) {
            $logo = imagecreatefromstring($disk->get($logoPath));
            $logoW = imagesx($logo);
            $logoH = imagesy($logo);
            $destW = $width * 0.3;
            $destH = $logoH * ($destW / $logoW);
            imagecopyresampled($canvas, $logo, ($width - $destW) / 2, ($height - $destH) / 2, 0, 0, $destW, $destH, $logoW, $logoH);
            imagedestroy($logo);
        }

        $text = $item->name ?? (string) $item->id;
        if ($settings->qr_text_redundancy) {
            $font = 5;
            $textW = imagefontwidth($font) * strlen($text);
            $black = imagecolorallocate($canvas, 0, 0, 0);
            imagestring($canvas, $font, ($width - $textW) / 2, $height + 2, $text, $black);
        }

        ob_start();
        imagepng($canvas);
        $pngData = ob_get_clean();
        imagedestroy($canvas);

        $formats = array_map('trim', explode(',', $settings->qr_formats ?? 'png,pdf'));
        $base = $this->directory.'/qr-CON-'.($item->name ? Str::slug($item->name) : $item->id);
        if (in_array('png', $formats)) {
            $disk->put($base.'.png', $pngData);
        }
        if (in_array('pdf', $formats)) {
            $pdf = new TCPDF();
            $pdf->AddPage();
            $tmp = tempnam(sys_get_temp_dir(), 'qr');
            file_put_contents($tmp, $pngData);
            $pdf->Image($tmp, 15, 15, 50, 50, 'PNG');
            unlink($tmp);
            if ($settings->qr_text_redundancy) {
                $pdf->SetY(70);
                $pdf->SetFont('helvetica', '', 12);
                $pdf->Cell(0, 0, $text, 0, 1, 'C');
            }
            $disk->put($base.'.pdf', $pdf->Output('', 'S'));
        }
    }
}
