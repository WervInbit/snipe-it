<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Services\Assets\LegacyAssetAssignmentCleanupService;
use Illuminate\Console\Command;

class FixupAssignedToWithoutAssignedType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'snipeit:assigned-to-fixup
                            {--debug : Display debugging output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears retired asset assignment state where assigned_type is missing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $assets = Asset::whereNull("assigned_type")->whereNotNull("assigned_to")->withTrashed();
        $this->withProgressBar($assets->get(), function (Asset $asset) {
            if($this->option("debug")) {
                $this->info("Clearing retired assignment state for asset id: " . $asset->id);
            }
            app(LegacyAssetAssignmentCleanupService::class)->clear($asset);
        });
        $this->newLine();
        $this->info("Retired asset assignment state is cleared");
    }
}
