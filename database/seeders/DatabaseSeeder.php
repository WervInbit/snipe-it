<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Allow mass assignment during seeding.
        Model::unguard();

        Schema::disableForeignKeyConstraints();

        if (! Setting::first()) {
            $this->call(SettingsSeeder::class);
        }

        $this->call([
            CategorySeeder::class,
            LocationSeeder::class,
            DepartmentSeeder::class,
            StatuslabelSeeder::class,
            ManufacturerSeeder::class,
            SupplierSeeder::class,
            UserSeeder::class,
            DepreciationSeeder::class,
            DeviceAttributeSeeder::class,
            AttributeTestSeeder::class,
            RolePermissionSeeder::class,
            DemoAssetsSeeder::class,
        ]);

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

        Artisan::call('snipeit:sync-asset-locations', ['--output' => 'all']);
        Log::info(Artisan::output());

        Schema::enableForeignKeyConstraints();
        Model::reguard();

        DB::table('imports')->delete();
        DB::table('maintenances')->delete();
        DB::table('requested_assets')->delete();

        DB::statement('ALTER TABLE imports AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE maintenances AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE requested_assets AUTO_INCREMENT = 1');
    }
}
