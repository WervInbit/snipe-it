<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Services\Assets\LegacyAssetAssignmentCleanupService;
use Illuminate\Console\Command;

class FixMismatchedAssetsAndLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'snipeit:fix-assets-and-logs {--dryrun : Run the sync process but don\'t update the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reports or clears retired asset assignment state without rebuilding it from checkout logs.';

    /**
     * Is dry-run?
     *
     * @var bool
     */
    private $dryrun = false;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->option('dryrun')) {
            $this->dryrun = true;
        }

        if ($this->dryrun) {
            $this->info('This is a DRY RUN - no changes will be saved.');
        }

        $assignmentCount = 0;
        $assets = Asset::whereNotNull('assigned_to')
            ->orderBy('id', 'ASC')->get();
        foreach ($assets as $asset) {
            $assignmentCount++;
            $this->warn('Asset '.$asset->id.' ('.$asset->asset_tag.') contains retired assignment state.');
            if (! $this->dryrun) {
                app(LegacyAssetAssignmentCleanupService::class)->clear($asset);
                $this->info('Retired assignment state cleared; historical logs were not changed.');
            }
        }
        $this->info($assignmentCount.' assets with retired assignment state.');
    }
}
