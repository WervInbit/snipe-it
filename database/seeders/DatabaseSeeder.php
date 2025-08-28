<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\Group;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Unguard models so they can be mass‑assigned during seeding
        Model::unguard();

        // Disable foreign key checks globally while seeding
        Schema::disableForeignKeyConstraints();

        // Only create default settings if they do not exist in the db.
        if (! Setting::first()) {
            // factory(Setting::class)->create();
            $this->call(SettingsSeeder::class);
        }

        // Run all core seeders
        $this->call(CompanySeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(LocationSeeder::class);
        $this->call(DepartmentSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(DepreciationSeeder::class);
        $this->call(ManufacturerSeeder::class);
        $this->call(SupplierSeeder::class);
        $this->call(AssetModelSeeder::class);
        $this->call(StatuslabelSeeder::class);
        $this->call(AccessorySeeder::class);
        $this->call(CustomFieldSeeder::class);
        $this->call(AssetSeeder::class);
        $this->call(LicenseSeeder::class);
        $this->call(ComponentSeeder::class);
        $this->call(ConsumableSeeder::class);
        // Seed default test types
        $this->call(TestTypeSeeder::class);
        $this->call(ActionlogSeeder::class);
        $this->call(RolePermissionSeeder::class);

        // Seed default roles with permissions
        Group::updateOrCreate(
            ['name' => 'Refurbisher'],
            ['permissions' => json_encode(['scanning' => 1])]
        );

        Group::updateOrCreate(
            ['name' => 'Senior Refurbisher'],
            ['permissions' => json_encode([
                'scanning'      => 1,
                'tests.execute' => 1,
            ])]
        );

        Group::updateOrCreate(
            ['name' => 'Supervisor'],
            ['permissions' => json_encode([
                'scanning'      => 1,
                'tests.execute' => 1,
                'assets.create' => 1,
                'assets.delete' => 1,
                'tests.delete'  => 1,
            ])]
        );

        Group::updateOrCreate(
            ['name' => 'Admin'],
            ['permissions' => json_encode([
                'scanning'           => 1,
                'tests.execute'      => 1,
                'assets.create'      => 1,
                'assets.delete'      => 1,
                'tests.delete'       => 1,
                'audits.view'        => 1,
                'config.qr_tooltips' => 1,
            ])]
        );

        // Create demo assets
        $this->call(DemoAssetsSeeder::class);

        // Synchronise asset locations
        Artisan::call('snipeit:sync-asset-locations', ['--output' => 'all']);
        $output = Artisan::output();
        Log::info($output);

        // Re‑enable foreign key checks once seeding is complete
        Schema::enableForeignKeyConstraints();

        // Reguard models to restore mass‑assignment protection
        Model::reguard();

        /*
         * Clean up some tables without using TRUNCATE.
         * TRUNCATE fails when other tables reference them via foreign keys.
         */
        DB::table('imports')->delete();
        DB::table('maintenances')->delete();
        DB::table('requested_assets')->delete();

        // Reset auto‑increment counters on those tables
        DB::statement('ALTER TABLE imports AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE maintenances AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE requested_assets AUTO_INCREMENT = 1');
    }
}
