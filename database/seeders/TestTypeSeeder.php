<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TestType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestTypeSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('test_types')->truncate();
        Schema::enableForeignKeyConstraints();

        $types = [
            ['name' => 'External Cleaning', 'description' => 'External surfaces cleaned', 'tooltip' => 'Placeholder tooltip for External Cleaning', 'slug' => 'external-cleaning'],
            ['name' => 'Internal Cleaning', 'description' => 'Internal components cleaned', 'tooltip' => 'Placeholder tooltip for Internal Cleaning', 'slug' => 'internal-cleaning'],
            ['name' => 'Screen', 'tooltip' => 'Placeholder tooltip for Screen', 'slug' => 'screen'],
            ['name' => 'Battery', 'tooltip' => 'Placeholder tooltip for Battery', 'slug' => 'battery'],
            ['name' => 'Keyboard', 'tooltip' => 'Placeholder tooltip for Keyboard', 'slug' => 'keyboard'],
            ['name' => 'Ports', 'tooltip' => 'Placeholder tooltip for Ports', 'slug' => 'ports'],
            ['name' => 'Audio', 'tooltip' => 'Placeholder tooltip for Audio', 'slug' => 'audio'],
            ['name' => 'Camera', 'tooltip' => 'Placeholder tooltip for Camera', 'slug' => 'camera'],
            ['name' => 'Microphone', 'tooltip' => 'Placeholder tooltip for Microphone', 'slug' => 'microphone'],
            ['name' => 'Touchscreen', 'tooltip' => 'Placeholder tooltip for Touchscreen', 'slug' => 'touchscreen'],
            ['name' => 'Sensors', 'tooltip' => 'Placeholder tooltip for Sensors', 'slug' => 'sensors'],
            ['name' => 'Bluetooth', 'tooltip' => 'Placeholder tooltip for Bluetooth', 'slug' => 'bluetooth'],
        ];

        foreach ($types as $type) {
            TestType::updateOrCreate(['slug' => $type['slug']], $type);
        }
    }
}
