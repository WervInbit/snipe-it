<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class ProductionFoundationSeeder extends Seeder
{
    public function run(): void
    {
        Model::unguarded(function (): void {
            $this->call([
                SettingsSeeder::class,
                ProductionPermissionGroupSeeder::class,
                ProductionStatusLabelSeeder::class,
                ProductionSupplierSeeder::class,
                DeviceAttributeSeeder::class,
                DevicePresetSeeder::class,
                DeviceComponentCatalogSeeder::class,
                AttributeTestSeeder::class,
            ]);
        });
    }
}
