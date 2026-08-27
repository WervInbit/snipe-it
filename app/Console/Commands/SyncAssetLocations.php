<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncAssetLocations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'snipeit:sync-asset-locations {--output= : info|warn|error|all} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retired: legacy asset assignments no longer control asset locations.';

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
    public function handle(): int
    {
        $this->error(
            'This command is retired because asset checkout/checkin assignments no longer control locations.'
        );

        return self::FAILURE;
    }
}
