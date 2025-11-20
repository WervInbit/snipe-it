<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Services\QrLabelService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DumpQrLabelHtml extends Command
{
    protected $signature = 'qr:debug-html {asset_tag} {template?}';

    protected $description = 'Dump the rendered QR label HTML for an asset/template combination.';

    public function handle(QrLabelService $service): int
    {
        $asset = Asset::where('asset_tag', $this->argument('asset_tag'))->first();
        if (! $asset) {
            $this->error('Asset not found');
            return 1;
        }

        $template = $this->argument('template');
        $pdf = app(\App\Services\QrCodeService::class)->pdf(
            $asset->asset_tag,
            null,
            null,
            $template,
            [
                'top' => [],
                'bottom' => [
                    $asset->name ?? optional($asset->model)->name ?? '',
                    trans('admin/hardware/form.tag').': '.$asset->asset_tag,
                ],
            ]
        );

        Storage::disk('local')->put('qr-debug-'.$asset->asset_tag.'.pdf', $pdf);
        $this->info('Dumped pdf to storage/app/qr-debug-'.$asset->asset_tag.'.pdf');
        return 0;
    }
}
