<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Accessory;
use App\Models\Component;
use App\Models\Consumable;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Dompdf\Dompdf;
use Illuminate\Support\Collection;

class QrLabelService
{
    protected string $directory = 'labels';
    // Increment to invalidate previously generated image filenames
    protected string $version = 'v13';

    /**
     * Generate PNG and PDF labels for an asset.
     */
    public function generate(Asset $asset, ?string $template = null): void
    {
        $settings = Setting::getSettings() ?? (object) [];
        $template = $template ?? ($settings->qr_label_template ?? config('qr_templates.default'));
        $disk = Storage::disk('public');
        $disk->makeDirectory($this->directory);

        $logo = ($settings->qr_logo ?? null) ?: ($settings->label_logo ?? null);
        $logoPath = ($logo && $disk->exists($logo)) ? $disk->path($logo) : null;
        $data = $asset->asset_tag;
        $label = ($settings->qr_text_redundancy ?? false) ? $asset->asset_tag : null;
        $formats = array_map(function ($format) {
            return strtolower(trim($format));
        }, explode(',', $settings->qr_formats ?? 'png,pdf,qr'));
        $qr = app(QrCodeService::class);

        $caption = $this->assetLabelBlocks($asset, $settings, $template);

        if (in_array('png', $formats)) {
            $png = $qr->png($data, $label, $logoPath, $template);
            $disk->put($this->path($asset, 'png', $template), $png);
        }

        if (in_array('qr', $formats)) {
            $this->generateRaw($asset, $logoPath);
        }

        if (in_array('pdf', $formats)) {
            $pdf = $qr->pdf($data, $label, $logoPath, $template, $caption);
            $disk->put($this->path($asset, 'pdf', $template), $pdf);
        }
    }

    /**
     * Return URL for label in desired format.
     */
    public function url(Asset $asset, string $format = 'png', ?string $template = null): string
    {
        $settings = Setting::getSettings() ?? (object) [];
        $template = $template ?? ($settings->qr_label_template ?? config('qr_templates.default'));
        $disk = Storage::disk('public');

        if ($format === 'qr') {
            $rawPath = $this->path($asset, 'png', 'qr-only');
            if (! $disk->exists($rawPath)) {
                $this->generateRaw($asset);
            }

            return $disk->url($rawPath);
        }

        $file = $this->path($asset, $format, $template);
        if (! $disk->exists($file)) {
            $this->generate($asset, $template);
        }

        return $disk->url($file);
    }

    protected function path(Asset $asset, string $format, string $template): string
    {
        return $this->directory.'/qr-'.$this->version.'-'.$template.'-'.Str::slug($asset->asset_tag).'.'.$format;
    }

    /**
     * Render and return a PDF binary for a single asset (always generates PDF, regardless of qr_formats).
     */
    public function pdfBinary(Asset $asset, ?string $template = null): string
    {
        $settings = Setting::getSettings() ?? (object) [];
        $template = $template ?? ($settings->qr_label_template ?? config('qr_templates.default'));
        $disk = Storage::disk('public');
        $logo = ($settings->qr_logo ?? null) ?: ($settings->label_logo ?? null);
        $logoPath = ($logo && $disk->exists($logo)) ? $disk->path($logo) : null;
        $qr = app(QrCodeService::class);
        $caption = $this->assetLabelBlocks($asset, $settings, $template);

        return $qr->pdf(
            $asset->asset_tag,
            ($settings->qr_text_redundancy ?? false) ? $asset->asset_tag : null,
            $logoPath,
            $template,
            $caption
        );
    }

    /**
     * Generate a combined PDF for multiple assets.
     *
     * @param \Illuminate\Support\Collection<Asset> $assets
     */
    public function batchPdf(Collection $assets, ?string $template = null): string
    {
        $settings = Setting::getSettings() ?? (object) [];
        $template = $template ?? ($settings->qr_label_template ?? config('qr_templates.default'));
        $disk = Storage::disk('public');
        $logo = ($settings->qr_logo ?? null) ?: ($settings->label_logo ?? null);
        $logoPath = ($logo && $disk->exists($logo)) ? $disk->path($logo) : null;
        $tpls = config('qr_templates.templates');
        $tpl = $tpls[$template] ?? reset($tpls);
        $qr = app(QrCodeService::class);

        $fragments = [];
        $count = $assets->count();
        foreach ($assets as $index => $asset) {
            $data = $asset->asset_tag;
            $label = ($settings->qr_text_redundancy ?? false) ? $asset->asset_tag : null;
            $caption = $this->assetLabelBlocks($asset, $settings, $template);
            $png = $qr->png($data, $label, $logoPath, $template);
            $pageBreak = $index < ($count - 1);
            $fragments[] = $qr->renderLabelFragment($png, $tpl, $caption, $pageBreak);
        }

        [$html, $paper] = $qr->renderLabelDocument($tpl, $fragments);

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paper);
        $dompdf->render();

        return $dompdf->output();
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

        $data = 'ACC:'.$item->qr_uid;
        $text = $item->name ?? (string) $item->id;
        $label = $settings->qr_text_redundancy ? $text : null;
        $logo = $settings->qr_logo ?: $settings->label_logo;
        $logoPath = ($logo && $disk->exists($logo)) ? $disk->path($logo) : null;
        $formats = array_map(function ($format) {
            return strtolower(trim($format));
        }, explode(',', $settings->qr_formats ?? 'png,pdf'));
        $base = $this->directory.'/qr-ACC-'.($item->name ? Str::slug($item->name) : $item->id);
        $qr = app(QrCodeService::class);

        if (in_array('png', $formats)) {
            $disk->put($base.'.png', $qr->png($data, $label, $logoPath));
        }
        if (in_array('pdf', $formats)) {
            $disk->put($base.'.pdf', $qr->pdf($data, $label, $logoPath));
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

        $data = 'CMP:'.$item->qr_uid;
        $text = $item->name ?? (string) $item->id;
        $label = $settings->qr_text_redundancy ? $text : null;
        $logo = $settings->qr_logo ?: $settings->label_logo;
        $logoPath = ($logo && $disk->exists($logo)) ? $disk->path($logo) : null;
        $formats = array_map(function ($format) {
            return strtolower(trim($format));
        }, explode(',', $settings->qr_formats ?? 'png,pdf'));
        $base = $this->directory.'/qr-CMP-'.($item->name ? Str::slug($item->name) : $item->id);
        $qr = app(QrCodeService::class);

        if (in_array('png', $formats)) {
            $disk->put($base.'.png', $qr->png($data, $label, $logoPath));
        }
        if (in_array('pdf', $formats)) {
            $disk->put($base.'.pdf', $qr->pdf($data, $label, $logoPath));
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

        $data = 'CON:'.$item->qr_uid;
        $text = $item->name ?? (string) $item->id;
        $label = $settings->qr_text_redundancy ? $text : null;
        $logo = $settings->qr_logo ?: $settings->label_logo;
        $logoPath = ($logo && $disk->exists($logo)) ? $disk->path($logo) : null;
        $formats = array_map('trim', explode(',', $settings->qr_formats ?? 'png,pdf'));
        $base = $this->directory.'/qr-CON-'.($item->name ? Str::slug($item->name) : $item->id);
        $qr = app(QrCodeService::class);

        if (in_array('png', $formats)) {
            $disk->put($base.'.png', $qr->png($data, $label, $logoPath));
        }
        if (in_array('pdf', $formats)) {
            $disk->put($base.'.pdf', $qr->pdf($data, $label, $logoPath));
        }
    }

    /**
     * Build the caption lines rendered under the QR code.
     *
     * @param object $settings
     * @param string|null $template
     * @return array<int, string>
     */
    protected function assetLabelBlocks(Asset $asset, object $settings, ?string $template = null): array
    {
        $lines = [];

        $templateKey = $template ?? config('qr_templates.default');
        $assetTag = trim((string) $asset->asset_tag);
        $serial = trim((string) $asset->serial);

        if (Str::contains($templateKey, 's0929120')) {
            // Ultra-compact label: show asset tag and serial (centered)
            if ($assetTag !== '') {
                $lines[] = Str::limit($assetTag, 48);
            }
            if ($serial !== '') {
                $lines[] = 'SN: '.Str::limit($serial, 48);
            }
        } else {
            $assetName = trim((string) ($asset->name
                ?: optional($asset->model)->name
                ?: optional($asset->modelNumber)->label
                ?: optional($asset->modelNumber)->code));

            if ($assetName !== '') {
                $lines[] = Str::limit($assetName, 48);
            }

            if ($assetTag !== '') {
                $lines[] = trans('admin/hardware/form.tag').': '.Str::limit($assetTag, 48);
            }

            if ($serial !== '') {
                $lines[] = trans('admin/hardware/form.serial').': '.Str::limit($serial, 48);
            }
        }

        if (empty($lines)) {
            $lines[] = trans('general.qr_printed_on_date', ['date' => now()->toDateString()]);
        }

        return [
            'top' => [],
            'bottom' => array_values($lines),
        ];
    }

    protected function generateRaw(Asset $asset, ?string $logoPath = null): void
    {
        $settings = Setting::getSettings() ?? (object) [];
        $disk = Storage::disk('public');
        $disk->makeDirectory($this->directory);

        $logo = ($settings->qr_logo ?? null) ?: ($settings->label_logo ?? null);
        $logoPath = $logoPath ?? (($logo && $disk->exists($logo)) ? $disk->path($logo) : null);

        $qr = app(QrCodeService::class);
        $png = $qr->png($asset->asset_tag, null, $logoPath, null);
        $disk->put($this->path($asset, 'png', 'qr-only'), $png);
    }
}
