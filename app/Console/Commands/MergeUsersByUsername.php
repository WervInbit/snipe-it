<?php

namespace App\Console\Commands;

use App\Events\UserMerged;
use App\Models\User;
use App\Services\Assets\LegacyAssetAssignmentCleanupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MergeUsersByUsername extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'snipeit:merge-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command allows you to merge the history of users. It looks for users without an email address as their username and merges them into the version that does have an email username.';

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
        $users = User::where('username', 'LIKE', '%@%')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();
        $this->info($users->count().' total non-deleted users whose usernames contain a @ symbol.');

        foreach ($users->groupBy(fn (User $user) => $this->localUsername($user->username)) as $localUsername => $destinations) {
            $this->info('Checking against username '.$localUsername.'.');

            if ($destinations->count() !== 1) {
                $this->warn(
                    'Skipping '.$localUsername.' because multiple destination usernames match this account.'
                );
                continue;
            }

            $destinationId = $destinations->first()->id;
            $sourceIds = User::where('username', $localUsername)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->pluck('id');

            foreach ($sourceIds as $sourceId) {
                DB::transaction(function () use ($destinationId, $sourceId): void {
                    $user = User::query()->lockForUpdate()->findOrFail($destinationId);
                    $bad_user = User::query()
                        ->with(
                            'assets',
                            'manager',
                            'userlog',
                            'licenses',
                            'consumables',
                            'accessories',
                            'managedLocations',
                            'uploads'
                        )
                        ->lockForUpdate()
                        ->findOrFail($sourceId);

                    $this->info($bad_user->username.' ('.$bad_user->id.') will be merged into '.$user->username.' ('.$user->id.')');

                    foreach ($bad_user->assets as $asset) {
                        $this->info('Clearing retired assignment on asset '.$asset->asset_tag.' '.$asset->id);
                        app(LegacyAssetAssignmentCleanupService::class)->clear($asset);
                    }

                    foreach ($bad_user->licenses as $license) {
                        $this->info('Updating license '.$license->name.' '.$license->id.' to user '.$user->id);
                        $bad_user->licenses()->updateExistingPivot($license->id, ['assigned_to' => $user->id]);
                    }

                    foreach ($bad_user->consumables as $consumable) {
                        $this->info('Updating consumable '.$consumable->id.' to user '.$user->id);
                        $bad_user->consumables()->updateExistingPivot($consumable->id, ['assigned_to' => $user->id]);
                    }

                    foreach ($bad_user->accessories as $accessory) {
                        $this->info('Updating accessory '.$accessory->id.' to user '.$user->id);
                        $bad_user->accessories()->updateExistingPivot($accessory->id, ['assigned_to' => $user->id]);
                    }

                    foreach ($bad_user->userlog as $log) {
                        $this->info('Updating action log record '.$log->id.' to user '.$user->id);
                        $log->target_id = $user->id;
                        $log->save();
                    }

                    $this->info('Updating managed user records to user '.$user->id);
                    User::where('manager_id', $bad_user->id)->update(['manager_id' => $user->id]);

                    foreach ($bad_user->managedLocations as $managedLocation) {
                        $this->info('Updating managed location record '.$managedLocation->name.' to manager '.$user->id);
                        $managedLocation->manager_id = $user->id;
                        $managedLocation->save();
                    }

                    foreach ($bad_user->uploads as $upload) {
                        $this->info('Updating upload log record '.$upload->id.' to user '.$user->id);
                        $upload->item_id = $user->id;
                        $upload->save();
                    }

                    $this->info('Soft-deleting the merged user.');
                    $bad_user->delete();

                    event(new UserMerged($bad_user, $user, null));
                });
            }
        }

        return self::SUCCESS;
    }

    private function localUsername(string $username): string
    {
        return trim(explode('@', trim($username), 2)[0]);
    }
}
