<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestTypeSeeder extends Seeder
{
    public function run()
    {
        DB::table('test_types')->truncate();

        $types = [
            ['name' => 'Screen', 'tooltip' => 'Placeholder tooltip for Screen'],
            ['name' => 'Battery', 'tooltip' => 'Placeholder tooltip for Battery'],
            ['name' => 'Keyboard', 'tooltip' => 'Placeholder tooltip for Keyboard'],
            ['name' => 'Ports', 'tooltip' => 'Placeholder tooltip for Ports'],
            ['name' => 'Audio', 'tooltip' => 'Placeholder tooltip for Audio'],
            ['name' => 'Camera', 'tooltip' => 'Placeholder tooltip for Camera'],
            ['name' => 'Microphone', 'tooltip' => 'Placeholder tooltip for Microphone'],
            ['name' => 'Touchscreen', 'tooltip' => 'Placeholder tooltip for Touchscreen'],
            ['name' => 'Sensors', 'tooltip' => 'Placeholder tooltip for Sensors'],
            ['name' => 'Bluetooth', 'tooltip' => 'Placeholder tooltip for Bluetooth'],
        ];

        DB::table('test_types')->insert($types);
    }
}
