<?php

namespace Database\Seeders;

use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\Location;
use App\Models\License;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeder for the action_logs table.
 * Clears existing logs safely, ensures prerequisites, and then seeds new logs.
 */
class ActionlogSeeder extends Seeder
{
    public function run(): void
    {
        // Safely clear existing logs; truncate would fail if other tables reference action_logs
        Schema::disableForeignKeyConstraints();
        Actionlog::query()->delete();
        DB::statement('ALTER TABLE action_logs AUTO_INCREMENT = 1');
        Schema::enableForeignKeyConstraints();

        // Ensure we have assets, locations, and licenses to work with
        if (!Asset::count()) {
            $this->call(AssetSeeder::class);
        }
        if (!Location::count()) {
            $this->call(LocationSeeder::class);
        }
        if (!License::count()) {
            $this->call(LicenseSeeder::class);
        }

        // Identify the admin (creates one if necessary)
        $admin = User::where('permissions->superuser', '1')->first()
            ?? User::factory()->firstAdmin()->create();

        // Create checkout logs
        Actionlog::factory()
            ->count(300)
            ->assetCheckoutToUser()
            ->create(['created_by' => $admin->id]);

        Actionlog::factory()
            ->count(100)
            ->assetCheckoutToLocation()
            ->create(['created_by' => $admin->id]);

        Actionlog::factory()
            ->count(20)
            ->licenseCheckoutToUser()
            ->create(['created_by' => $admin->id]);
    }
}
