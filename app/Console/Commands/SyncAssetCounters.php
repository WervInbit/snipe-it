<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncAssetCounters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'snipeit:counter-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retired: checkout, check-in, and request counters are historical.';

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
     * @return int
     */
    public function handle(): int
    {
        $this->error(
            'This command is retired because asset checkout, check-in, and request counters are historical.'
        );

        return self::FAILURE;
    }
}
