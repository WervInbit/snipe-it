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
    protected string $version = 'v3';

    /**
     * Generate PNG and PDF labels for an asset.
     */
    public function generate(Asset $asset, ?string $template = null): void
    {
        $settings = Setting::getSettings();
        $template = $template ?? ($settings->qr_label_template ?? config('qr_templates.default'));
        $disk = Storage::disk('public');
        $disk->makeDirectory($this->directory);

        $logo = $settings->qr_logo ?: $settings->label_logo;
        $logoPath = ($logo && $disk->exists($logo)) ? $disk->path($logo) : null;
        $data = $asset->asset_tag;
        $label = $settings->qr_text_redundancy ? $asset->asset_tag : null;
        $formats = array_map('trim', explode(',', $settings->qr_formats ?? 'png,pdf'));
        $qr = app(QrCodeService::class);

        $caption = $asset->name ?: optional($asset->model)->name;
        if (! $caption) {
            $caption = trans('general.qr_printed_on_date', ['date' => now()->toDateString()]);
        }

        if (in_array('png', $formats)) {
            $png = $qr->png($data, $label, $logoPath, $template);
            $disk->put($this->path($asset, 'png', $template), $png);
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
        $settings = Setting::getSettings();
        $template = $template ?? ($settings->qr_label_template ?? config('qr_templates.default'));
        $disk = Storage::disk('public');
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
     * Generate a combined PDF for multiple assets.
     *
     * @param \Illuminate\Support\Collection<Asset> $assets
     */
    public function batchPdf(Collection $assets, ?string $template = null): string
    {
        $settings = Setting::getSettings();
        $template = $template ?? ($settings->qr_label_template ?? config('qr_templates.default'));
        $disk = Storage::disk('public');
        $logo = $settings->qr_logo ?: $settings->label_logo;
        $logoPath = ($logo && $disk->exists($logo)) ? $disk->path($logo) : null;
        $tpls = config('qr_templates.templates');
        $tpl = $tpls[$template] ?? reset($tpls);
        $width = $tpl['width_mm'];
        $height = $tpl['height_mm'];
        $qr = app(QrCodeService::class);

        $html = '<html><body style="margin:0;padding:0;">';
        $count = $assets->count();
        foreach ($assets as $i => $asset) {
            $data = $asset->asset_tag;
            $label = $settings->qr_text_redundancy ? $asset->asset_tag : null;
            $caption = $asset->name ?: optional($asset->model)->name;
            if (! $caption) {
                $caption = trans('general.qr_printed_on_date', ['date' => now()->toDateString()]);
            }
            $png = $qr->png($data, $label, $logoPath, $template);
            $captionHtml = $caption ? '<div style="margin-top:2px;font-size:10px;text-align:center;">' . htmlspecialchars($caption, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>' : '';
            $style = 'width:' . $width . 'mm;height:' . $height . 'mm;display:flex;flex-direction:column;justify-content:center;align-items:center;';
            if ($i < $count - 1) {
                $style .= 'page-break-after:always;';
            }
            $html .= '<div style="' . $style . '"><img src="data:image/png;base64,' . base64_encode($png) . '" style="max-height:80%;max-width:100%;" />' . $captionHtml . '</div>';
        }
        $html .= '</body></html>';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, $width * 72 / 25.4, $height * 72 / 25.4]);
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
        $formats = array_map('trim', explode(',', $settings->qr_formats ?? 'png,pdf'));
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
        $formats = array_map('trim', explode(',', $settings->qr_formats ?? 'png,pdf'));
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
}
