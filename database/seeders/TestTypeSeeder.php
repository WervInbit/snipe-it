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
            ['name' => 'Cleaning - external', 'slug' => 'cleaning-external', 'tooltip' => 'Wipe down exterior surfaces'],
            ['name' => 'Cleaning - internal', 'slug' => 'cleaning-internal', 'tooltip' => 'Clean internal components and remove dust'],
            ['name' => 'Keyboard',          'slug' => 'keyboard',          'tooltip' => 'Check that all keys register correctly'],
            ['name' => 'Screen',            'slug' => 'screen',            'tooltip' => 'Verify display for dead pixels and proper brightness'],
            ['name' => 'Touchpad',          'slug' => 'touchpad',          'tooltip' => 'Ensure touchpad responds and gestures work'],
            ['name' => 'USB ports',         'slug' => 'usb-ports',         'tooltip' => 'Test each USB port for connectivity'],
            ['name' => 'SD slot',           'slug' => 'sd-slot',           'tooltip' => 'Insert SD card to confirm detection and transfer'],
            ['name' => 'CD/DVD drive',      'slug' => 'cd-dvd-drive',      'tooltip' => 'Insert disc to confirm drive reads and ejects properly'],
            ['name' => 'VGA',               'slug' => 'vga',               'tooltip' => 'Connect external monitor via VGA to verify output'],
            ['name' => 'HDMI',              'slug' => 'hdmi',              'tooltip' => 'Connect HDMI device to verify video and audio'],
            ['name' => 'CPU stress test',   'slug' => 'cpu-stress-test',   'tooltip' => 'Run stress test to ensure CPU stability'],
            ['name' => 'Battery',           'slug' => 'battery',           'tooltip' => 'Confirm battery charges and holds capacity'],
            ['name' => 'RAM',               'slug' => 'ram',               'tooltip' => 'Run memory diagnostics to confirm no errors'],
            ['name' => 'Webcam',            'slug' => 'webcam',            'tooltip' => 'Open camera application to verify video feed'],
            ['name' => 'Microphone',        'slug' => 'microphone',        'tooltip' => 'Record audio to ensure microphone works'],
            ['name' => 'Speakers',          'slug' => 'speakers',          'tooltip' => 'Play audio to confirm speakers function'],
            ['name' => 'Wi-Fi',             'slug' => 'wi-fi',             'tooltip' => 'Connect to a Wi-Fi network to verify connectivity'],
            ['name' => 'Bluetooth',         'slug' => 'bluetooth',         'tooltip' => 'Pair device via Bluetooth to verify communication'],
            ['name' => 'Ethernet',          'slug' => 'ethernet',          'tooltip' => 'Connect network cable to verify wired networking'],
            ['name' => 'Fingerprint scanner','slug' => 'fingerprint-scanner','tooltip' => 'Scan fingerprint to confirm recognition'],
        ];

        // Seed or update test types
        foreach ($types as $type) {
            TestType::updateOrCreate(['slug' => $type['slug']], $type);
        }
    }
}
