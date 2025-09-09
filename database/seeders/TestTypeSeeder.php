<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\TestType;

/**
 * Seed the test_types table while respecting foreign keys.
 */
class TestTypeSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * When test_types is referenced by test_results via a foreign key,
         * TRUNCATE will fail with SQLSTATE[42000] 1701.  The pattern below
         * deletes the dependent rows, deletes the parents, resets the
         * auto-increment counters, and then reseeds.
         */

        // Temporarily disable FK checks on this connection
        Schema::disableForeignKeyConstraints();

        // Delete rows in child table(s) referencing test_types
        DB::table('test_results')->delete();

        // Delete all test_type rows instead of truncate
        TestType::query()->delete();

        // Reset the auto-increment counters on both tables
        DB::statement('ALTER TABLE test_results AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE test_types AUTO_INCREMENT = 1');

        // Re-enable FK checks
        Schema::enableForeignKeyConstraints();

        // Define the seed data
        $types = [
            ['name' => 'Cleaning - external', 'slug' => 'cleaning-external', 'tooltip' => 'Placeholder tooltip for Cleaning - external'],
            ['name' => 'Cleaning - internal', 'slug' => 'cleaning-internal', 'tooltip' => 'Placeholder tooltip for Cleaning - internal'],
            ['name' => 'Screen',            'slug' => 'screen',            'tooltip' => 'Placeholder tooltip for Screen'],
            ['name' => 'Battery',           'slug' => 'battery',           'tooltip' => 'Placeholder tooltip for Battery'],
            ['name' => 'Keyboard',          'slug' => 'keyboard',          'tooltip' => 'Placeholder tooltip for Keyboard'],
            ['name' => 'Touchpad',          'slug' => 'touchpad',          'tooltip' => 'Placeholder tooltip for Touchpad'],
            ['name' => 'USB ports',         'slug' => 'usb-ports',         'tooltip' => 'Placeholder tooltip for USB ports'],
            ['name' => 'Audio',             'slug' => 'audio',             'tooltip' => 'Placeholder tooltip for Audio'],
            ['name' => 'Camera',            'slug' => 'camera',            'tooltip' => 'Placeholder tooltip for Camera'],
            ['name' => 'Microphone',        'slug' => 'microphone',        'tooltip' => 'Placeholder tooltip for Microphone'],
            ['name' => 'Touchscreen',       'slug' => 'touchscreen',       'tooltip' => 'Placeholder tooltip for Touchscreen'],
            ['name' => 'Sensors',           'slug' => 'sensors',           'tooltip' => 'Placeholder tooltip for Sensors'],
            ['name' => 'Bluetooth',         'slug' => 'bluetooth',         'tooltip' => 'Placeholder tooltip for Bluetooth'],
            ['name' => 'Wi-Fi',             'slug' => 'wi-fi',             'tooltip' => 'Placeholder tooltip for Wi-Fi'],
            ['name' => 'Cellular',          'slug' => 'cellular',          'tooltip' => 'Placeholder tooltip for Cellular'],
        ];

        // Seed or update test types
        foreach ($types as $type) {
            TestType::updateOrCreate(['slug' => $type['slug']], $type);
        }
    }
}
