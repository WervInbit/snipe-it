<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\User;
use App\Services\Assets\LegacyAssetAssignmentCleanupService;
use App\Services\Users\UserRestoreBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class RestoreDeletedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'snipeit:restore-users {--start_date=} {--end_date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore soft-deleted users without replaying retired checkout history.';

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
    public function handle(
        UserRestoreBackupService $backupService,
        LegacyAssetAssignmentCleanupService $legacyAssignmentCleanup
    ): int {
        $start_date = $this->option('start_date');
        $end_date = $this->option('end_date');

        if (($start_date == '') || ($end_date == '')) {
            $this->error('All fields are required.');

            return self::FAILURE;
        }

        $validator = Validator::make([
            'start_date' => $start_date,
            'end_date' => $end_date,
        ], [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        $users = User::onlyTrashed()
            ->whereBetween('deleted_at', [$start_date, $end_date])
            ->orderBy('id')
            ->get();
        $this->info(
            'There are ' . $users->count() . ' users deleted between ' . $start_date . ' and ' . $end_date
        );

        if ($users->isEmpty()) {
            return self::SUCCESS;
        }

        $this->warn('Making a backup!');
        if (! $backupService->run()) {
            $this->error('Backup failed; no users were restored.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($users, $legacyAssignmentCleanup): void {
            foreach ($users as $user) {
                $this->warn('Restoring user ' . $user->username . '!');

                Asset::withTrashed()
                    ->where('assigned_to', $user->id)
                    ->whereIn('assigned_type', array_values(array_unique([
                        (new User())->getMorphClass(),
                        User::class,
                    ])))
                    ->eachById(function (Asset $asset) use ($legacyAssignmentCleanup): void {
                        $legacyAssignmentCleanup->clear($asset);
                    });

                if (! $user->restore()) {
                    throw new RuntimeException('Unable to restore user ' . $user->id . '.');
                }
            }
        });

        $this->info($users->count() . ' users restored; historical checkout state was not replayed.');

        return self::SUCCESS;
    }
}
