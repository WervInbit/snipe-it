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
            ['name' => 'Cleaning - external', 'slug' => 'cleaning-external', 'tooltip' => 'Wipe down exterior surfaces', 'category' => 'computer'],
            ['name' => 'Cleaning - internal', 'slug' => 'cleaning-internal', 'tooltip' => 'Clean internal components and remove dust', 'category' => 'computer'],
            ['name' => 'Keyboard',          'slug' => 'keyboard',          'tooltip' => 'Check that all keys register correctly', 'category' => 'computer'],
            ['name' => 'Screen',            'slug' => 'screen',            'tooltip' => 'Verify display for dead pixels and proper brightness', 'category' => 'computer'],
            ['name' => 'Touchpad',          'slug' => 'touchpad',          'tooltip' => 'Ensure touchpad responds and gestures work', 'category' => 'computer'],
            ['name' => 'USB ports',         'slug' => 'usb-ports',         'tooltip' => 'Test each USB port for connectivity', 'category' => 'computer'],
            ['name' => 'SD slot',           'slug' => 'sd-slot',           'tooltip' => 'Insert SD card to confirm detection and transfer', 'category' => 'computer'],
            ['name' => 'CD/DVD drive',      'slug' => 'cd-dvd-drive',      'tooltip' => 'Insert disc to confirm drive reads and ejects properly', 'category' => 'computer'],
            ['name' => 'VGA',               'slug' => 'vga',               'tooltip' => 'Connect external monitor via VGA to verify output', 'category' => 'computer'],
            ['name' => 'HDMI',              'slug' => 'hdmi',              'tooltip' => 'Connect HDMI device to verify video and audio', 'category' => 'computer'],
            ['name' => 'CPU stress test',   'slug' => 'cpu-stress-test',   'tooltip' => 'Run stress test to ensure CPU stability', 'category' => 'computer'],
            ['name' => 'Battery',           'slug' => 'battery',           'tooltip' => 'Confirm battery charges and holds capacity', 'category' => 'computer'],
            ['name' => 'RAM',               'slug' => 'ram',               'tooltip' => 'Run memory diagnostics to confirm no errors', 'category' => 'computer'],
            ['name' => 'Webcam',            'slug' => 'webcam',            'tooltip' => 'Open camera application to verify video feed', 'category' => 'computer'],
            ['name' => 'Microphone',        'slug' => 'microphone',        'tooltip' => 'Record audio to ensure microphone works', 'category' => 'computer'],
            ['name' => 'Speakers',          'slug' => 'speakers',          'tooltip' => 'Play audio to confirm speakers function', 'category' => 'computer'],
            ['name' => 'Wi-Fi',             'slug' => 'wi-fi',             'tooltip' => 'Connect to a Wi-Fi network to verify connectivity', 'category' => 'computer'],
            ['name' => 'Bluetooth',         'slug' => 'bluetooth',         'tooltip' => 'Pair device via Bluetooth to verify communication', 'category' => 'computer'],
            ['name' => 'Ethernet',          'slug' => 'ethernet',          'tooltip' => 'Connect network cable to verify wired networking', 'category' => 'computer'],
            ['name' => 'Fingerprint scanner','slug' => 'fingerprint-scanner','tooltip' => 'Scan fingerprint to confirm recognition', 'category' => 'computer'],
        ];

        // Seed or update test types
        foreach ($types as $type) {
            TestType::updateOrCreate(['slug' => $type['slug']], $type);
        }
    }
}
