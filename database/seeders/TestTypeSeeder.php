<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TestType;

class TestTypeSeeder extends Seeder
{
    public function run(): void
    {
        TestType::truncate();

        $types = [
            ['name' => 'External Cleaning', 'slug' => 'external-cleaning', 'tooltip' => 'Placeholder tooltip for External Cleaning'],
            ['name' => 'Internal Cleaning', 'slug' => 'internal-cleaning', 'tooltip' => 'Placeholder tooltip for Internal Cleaning'],
            ['name' => 'Screen', 'slug' => 'screen', 'tooltip' => 'Placeholder tooltip for Screen'],
            ['name' => 'Battery', 'slug' => 'battery', 'tooltip' => 'Placeholder tooltip for Battery'],
            ['name' => 'Keyboard', 'slug' => 'keyboard', 'tooltip' => 'Placeholder tooltip for Keyboard'],
            ['name' => 'Ports', 'slug' => 'ports', 'tooltip' => 'Placeholder tooltip for Ports'],
            ['name' => 'Audio', 'slug' => 'audio', 'tooltip' => 'Placeholder tooltip for Audio'],
            ['name' => 'Camera', 'slug' => 'camera', 'tooltip' => 'Placeholder tooltip for Camera'],
            ['name' => 'Microphone', 'slug' => 'microphone', 'tooltip' => 'Placeholder tooltip for Microphone'],
            ['name' => 'Touchscreen', 'slug' => 'touchscreen', 'tooltip' => 'Placeholder tooltip for Touchscreen'],
            ['name' => 'Sensors', 'slug' => 'sensors', 'tooltip' => 'Placeholder tooltip for Sensors'],
            ['name' => 'Bluetooth', 'slug' => 'bluetooth', 'tooltip' => 'Placeholder tooltip for Bluetooth'],
            ['name' => 'Wi-Fi', 'slug' => 'wi-fi', 'tooltip' => 'Placeholder tooltip for Wi-Fi'],
            ['name' => 'Cellular', 'slug' => 'cellular', 'tooltip' => 'Placeholder tooltip for Cellular'],
        ];

        foreach ($types as $type) {
            TestType::updateOrCreate(['slug' => $type['slug']], $type);
        }
    }
}
