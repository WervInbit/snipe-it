<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Accessory;
use App\Models\Component;
use App\Models\Consumable;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        if (in_array('png', $formats)) {
            $png = $qr->png($data, $label, $logoPath, $template);
            $disk->put($this->path($asset, 'png', $template), $png);
        }

        if (in_array('pdf', $formats)) {
            $pdf = $qr->pdf($data, $label, $logoPath, $template);
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
