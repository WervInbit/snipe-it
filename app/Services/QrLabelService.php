<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Accessory;
use App\Models\Component;
use App\Models\ComponentInstance;
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
        $this->generateForLabelTarget($asset, $template);
    }

    /**
     * Return URL for label in desired format.
     */
    public function url(Asset $asset, string $format = 'png', ?string $template = null): string
    {
        return $this->urlFor($asset, $format, $template);
    }

    /**
     * Return URL for a generated label target in the desired format.
     */
    public function urlFor(Asset|ComponentInstance $target, string $format = 'png', ?string $template = null): string
    {
        $settings = Setting::getSettings() ?? (object) [];
        $template = $template ?? ($settings->qr_label_template ?? config('qr_templates.default'));
        $disk = Storage::disk('public');

        if ($format === 'qr') {
            $rawPath = $this->pathFor($target, 'png', 'qr-only');
            if (! $disk->exists($rawPath)) {
                $this->generateRawForTarget($target);
            }

            return $disk->url($rawPath);
        }

        $file = $this->pathFor($target, $format, $template);
        if (! $disk->exists($file)) {
            $this->generateForLabelTarget($target, $template);
        }

        return $disk->url($file);
    }

    /**
     * Build preview HTML + CSS for the label widget so it matches the printed layout.
     *
     * @return array{html: string, styles: string, scale: float, preview_width_px: float, preview_height_px: float}
     */
    public function previewData(Asset $asset, ?string $template = null): array
    {
        return $this->previewDataFor($asset, $template);
    }

    /**
     * Build preview HTML + CSS for any supported label target.
     *
     * @return array{html: string, styles: string, scale: float, preview_width_px: float, preview_height_px: float}
     */
    public function previewDataFor(Asset|ComponentInstance $target, ?string $template = null): array
    {
        $settings = Setting::getSettings() ?? (object) [];
        $template = $template ?? ($settings->qr_label_template ?? config('qr_templates.default'));
        $templates = config('qr_templates.templates');
        $tpl = $templates[$template] ?? reset($templates);
        $qr = app(QrCodeService::class);
        $payload = $this->labelPayload($target, $settings, $template);

        $png = $qr->png(
            $payload['data'],
            $payload['label'],
            $payload['logo_path'],
            $template
        );

        $fragment = $qr->renderLabelFragment($png, $tpl, $payload['caption'], false);
        $styles = $qr->labelStyles($tpl, false);
        [$scale, $previewWidth, $previewHeight] = $this->previewScale($tpl);

        return [
            'html' => $fragment,
            'styles' => $styles,
            'scale' => $scale,
            'preview_width_px' => $previewWidth,
            'preview_height_px' => $previewHeight,
        ];
    }

    protected function path(Asset $asset, string $format, string $template): string
    {
        return $this->pathFor($asset, $format, $template);
    }

    protected function pathFor(Asset|ComponentInstance $target, string $format, string $template): string
    {
        $settings = Setting::getSettings() ?? (object) [];
        $payload = $this->labelPayload($target, $settings, $template);

        return $this->directory.'/qr-'.$this->version.'-'.$template.'-'.$payload['filename'].'.'.$format;
    }

    /**
     * Render and return a PDF binary for a single asset (always generates PDF, regardless of qr_formats).
     */
    public function pdfBinary(Asset $asset, ?string $template = null): string
    {
        return $this->pdfBinaryFor($asset, $template);
    }

    /**
     * Render and return a PDF binary for a single label target.
     */
    public function pdfBinaryFor(Asset|ComponentInstance $target, ?string $template = null): string
    {
        $settings = Setting::getSettings() ?? (object) [];
        $template = $template ?? ($settings->qr_label_template ?? config('qr_templates.default'));
        $qr = app(QrCodeService::class);
        $payload = $this->labelPayload($target, $settings, $template);

        return $qr->pdf(
            $payload['data'],
            $payload['label'],
            $payload['logo_path'],
            $template,
            $payload['caption']
        );
    }

    /**
     * Render and return a full-label PNG binary for a single asset.
     */
    public function pngBinary(Asset $asset, ?string $template = null): string
    {
        return $this->pngBinaryFor($asset, $template);
    }

    /**
     * Render and return a full-label PNG binary for a single label target.
     */
    public function pngBinaryFor(Asset|ComponentInstance $target, ?string $template = null): string
    {
        $settings = Setting::getSettings() ?? (object) [];
        $template = $template ?? ($settings->qr_label_template ?? config('qr_templates.default'));
        $qr = app(QrCodeService::class);
        $payload = $this->labelPayload($target, $settings, $template);

        return $qr->labelPng(
            $payload['data'],
            $payload['label'],
            $payload['logo_path'],
            $template,
            $payload['caption']
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

    protected function generateForLabelTarget(Asset|ComponentInstance $target, ?string $template = null): void
    {
        $settings = Setting::getSettings() ?? (object) [];
        $template = $template ?? ($settings->qr_label_template ?? config('qr_templates.default'));
        $disk = Storage::disk('public');
        $disk->makeDirectory($this->directory);

        $formats = $this->qrFormats($settings);
        $qr = app(QrCodeService::class);
        $payload = $this->labelPayload($target, $settings, $template);

        if (in_array('png', $formats, true)) {
            $png = $qr->labelPng(
                $payload['data'],
                $payload['label'],
                $payload['logo_path'],
                $template,
                $payload['caption']
            );
            $disk->put($this->pathFor($target, 'png', $template), $png);
        }

        if (in_array('qr', $formats, true)) {
            $this->generateRawForTarget($target, $payload['logo_path']);
        }

        if (in_array('pdf', $formats, true)) {
            $pdf = $qr->pdf(
                $payload['data'],
                $payload['label'],
                $payload['logo_path'],
                $template,
                $payload['caption']
            );
            $disk->put($this->pathFor($target, 'pdf', $template), $pdf);
        }
    }

    /**
     * @return array{data: string, label: string|null, logo_path: string|null, caption: array<string, array<int, string>>, filename: string}
     */
    protected function labelPayload(Asset|ComponentInstance $target, object $settings, ?string $template = null): array
    {
        $disk = Storage::disk('public');
        $logo = ($settings->qr_logo ?? null) ?: ($settings->label_logo ?? null);
        $logoPath = ($logo && $disk->exists($logo)) ? $disk->path($logo) : null;

        if ($target instanceof Asset) {
            $assetTag = trim((string) $target->asset_tag);

            return [
                'data' => $assetTag,
                'label' => ($settings->qr_text_redundancy ?? false) ? $assetTag : null,
                'logo_path' => $logoPath,
                'caption' => $this->assetLabelBlocks($target, $settings, $template),
                'filename' => Str::slug($assetTag ?: (string) $target->id),
            ];
        }

        $this->ensureComponentQrUid($target);
        $componentTag = trim((string) $target->component_tag);
        $labelText = $componentTag !== '' ? $componentTag : ($target->display_name ?: (string) $target->id);

        return [
            'data' => 'CMP:'.$target->qr_uid,
            'label' => ($settings->qr_text_redundancy ?? false) ? $labelText : null,
            'logo_path' => $logoPath,
            'caption' => $this->componentInstanceLabelBlocks($target, $settings, $template),
            'filename' => 'CMP-'.Str::slug($labelText ?: (string) $target->id),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function qrFormats(object $settings): array
    {
        return array_values(array_filter(array_map(
            fn ($format) => strtolower(trim((string) $format)),
            explode(',', $settings->qr_formats ?? 'png,pdf,qr')
        )));
    }

    protected function ensureComponentQrUid(ComponentInstance $component): void
    {
        if (filled($component->qr_uid)) {
            return;
        }

        $component->qr_uid = (string) Str::uuid();
        if ($component->exists) {
            $component->save();
        }
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
     * Generate labels for a tracked component instance.
     */
    public function generateForComponentInstance(ComponentInstance $item): void
    {
        $this->generateForLabelTarget($item);
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
     * @return array<string, array<int, string>>
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

    /**
     * Build caption lines for a tracked component instance.
     *
     * @param object $settings
     * @param string|null $template
     * @return array<string, array<int, string>>
     */
    protected function componentInstanceLabelBlocks(ComponentInstance $component, object $settings, ?string $template = null): array
    {
        $lines = [];

        $templateKey = $template ?? config('qr_templates.default');
        $componentTag = trim((string) $component->component_tag);
        $serial = trim((string) $component->serial);

        if (Str::contains($templateKey, 's0929120')) {
            if ($componentTag !== '') {
                $lines[] = Str::limit($componentTag, 48);
            }
            if ($serial !== '') {
                $lines[] = 'SN: '.Str::limit($serial, 48);
            }
        } else {
            $componentName = trim((string) $component->display_name);

            if ($componentName !== '') {
                $lines[] = Str::limit($componentName, 48);
            }

            if ($componentTag !== '') {
                $lines[] = trans('general.tag').': '.Str::limit($componentTag, 48);
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

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    protected function previewScale(array $tpl, int $maxWidthPx = 220, int $maxHeightPx = 180): array
    {
        $mmToPx = 96 / 25.4;
        $widthPx = max(1.0, (float) ($tpl['width_mm'] ?? 0) * $mmToPx);
        $heightPx = max(1.0, (float) ($tpl['height_mm'] ?? 0) * $mmToPx);
        $scale = min($maxWidthPx / $widthPx, $maxHeightPx / $heightPx);
        $scale = max(0.25, min($scale, 2.5));

        return [$scale, $widthPx * $scale, $heightPx * $scale];
    }

    protected function generateRaw(Asset $asset, ?string $logoPath = null): void
    {
        $this->generateRawForTarget($asset, $logoPath);
    }

    protected function generateRawForTarget(Asset|ComponentInstance $target, ?string $logoPath = null): void
    {
        $settings = Setting::getSettings() ?? (object) [];
        $disk = Storage::disk('public');
        $disk->makeDirectory($this->directory);

        $logo = ($settings->qr_logo ?? null) ?: ($settings->label_logo ?? null);
        $logoPath = $logoPath ?? (($logo && $disk->exists($logo)) ? $disk->path($logo) : null);

        $qr = app(QrCodeService::class);
        if ($target instanceof Asset) {
            $data = $target->asset_tag;
        } else {
            $this->ensureComponentQrUid($target);
            $data = 'CMP:'.$target->qr_uid;
        }
        $png = $qr->png($data, null, $logoPath, null);
        $disk->put($this->pathFor($target, 'png', 'qr-only'), $png);
    }
}
