<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Services\Assets\LegacyAssetAssignmentCleanupService;
use Illuminate\Console\Command;

class FixUpAssignedTypeWithoutAssignedTo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'snipeit:assigned-type-fixup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes up assets that have an assigned_type but no assigned_to';

    /**
     * Execute the console command.
     */
    public function handle(LegacyAssetAssignmentCleanupService $legacyAssignmentCleanup): int
    {
        Asset::withTrashed()
            ->whereNotNull('assigned_type')
            ->whereNull('assigned_to')
            ->orderBy('id')
            ->eachById(function (Asset $asset) use ($legacyAssignmentCleanup): void {
                $legacyAssignmentCleanup->clear($asset);
            });

        $this->info('Assets with an assigned_type but no assigned_to are fixed');

        return self::SUCCESS;
    }
}
