<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Asset;
use App\Models\Accessory;
use App\Models\Component;
use App\Models\Consumable;
use App\Services\QrLabelService;

class RegenerateQrLabels extends Command
{
    protected $signature = 'labels:regenerate {--chunk=200 : Chunk size}';
    protected $description = 'Regenerate QR label PNG/PDF files for all assets.';

    public function handle(): int
    {
        $chunk = (int) $this->option('chunk') ?: 200;
        $service = app(QrLabelService::class);
        $total = Asset::count();
        $this->info("Regenerating labels for {$total} assets in chunks of {$chunk}...");

        $processed = 0;
        Asset::orderBy('id')->chunk($chunk, function ($assets) use ($service, &$processed) {
            foreach ($assets as $asset) {
                try {
                    $service->generate($asset);
                    $processed++;
                } catch (\Throwable $e) {
                    $this->warn("Failed asset ID {$asset->id}: ".$e->getMessage());
                }
            }
            $this->line("Processed: {$processed}");
        });

        // Accessories
        if (class_exists(Accessory::class)) {
            $accTotal = Accessory::count();
            $this->info("Regenerating labels for {$accTotal} accessories...");
            Accessory::orderBy('id')->chunk($chunk, function ($items) use ($service) {
                foreach ($items as $i) {
                    try { $service->generateForAccessory($i); } catch (\Throwable $e) {}
                }
            });
        }

        // Components
        if (class_exists(Component::class)) {
            $cmpTotal = Component::count();
            $this->info("Regenerating labels for {$cmpTotal} components...");
            Component::orderBy('id')->chunk($chunk, function ($items) use ($service) {
                foreach ($items as $i) {
                    try { $service->generateForComponent($i); } catch (\Throwable $e) {}
                }
            });
        }

        // Consumables
        if (class_exists(Consumable::class)) {
            $conTotal = Consumable::count();
            $this->info("Regenerating labels for {$conTotal} consumables...");
            Consumable::orderBy('id')->chunk($chunk, function ($items) use ($service) {
                foreach ($items as $i) {
                    try { $service->generateForConsumable($i); } catch (\Throwable $e) {}
                }
            });
        }

        $this->info('QR label regeneration complete.');
        return Command::SUCCESS;
    }
}
