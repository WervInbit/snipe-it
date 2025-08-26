<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Services\QrLabelService;
use Illuminate\Console\Command;

class QrRegenerate extends Command
{
    protected $signature = 'qr:regenerate {assetTag?}';
    protected $description = 'Regenerate QR code labels for assets';

    public function __construct(protected QrLabelService $labels)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $tag = $this->argument('assetTag');
        if ($tag) {
            $asset = Asset::where('asset_tag', $tag)->first();
            if (! $asset) {
                $this->error('Asset not found.');
                return 1;
            }
            $this->labels->generate($asset);
            $this->info("Regenerated label for {$asset->asset_tag}");
            return 0;
        }

        Asset::chunk(100, function ($assets) {
            foreach ($assets as $asset) {
                $this->labels->generate($asset);
                $this->line("Regenerated label for {$asset->asset_tag}");
            }
        });
        $this->info('Labels regenerated.');
        return 0;
    }
}

