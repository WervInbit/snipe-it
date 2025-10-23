<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class LocationSeeder extends Seeder
{
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        Location::truncate();
        Schema::enableForeignKeyConstraints();

        $locations = [
            ['name' => 'Refurb Intake', 'location_type' => 'warehouse'],
            ['name' => 'Repair Bench', 'location_type' => 'workbench'],
            ['name' => 'QA Lab', 'location_type' => 'lab'],
            ['name' => 'Ready to Ship', 'location_type' => 'staging'],
        ];

        foreach ($locations as $index => $attributes) {
            Location::factory()->create(array_merge([
                'image' => sprintf('%02d.jpg', $index + 1),
            ], $attributes));
        }

        $src = public_path('/img/demo/locations/');
        $dst = 'locations'.'/';
        $del_files = Storage::files($dst);

        foreach ($del_files as $del_file) { // iterate files
            $file_to_delete = str_replace($src, '', $del_file);
            Log::debug('Deleting: '.$file_to_delete);
            try {
                Storage::disk('public')->delete($dst.$del_file);
            } catch (\Exception $e) {
                Log::debug($e);
            }
        }

        $add_files = glob($src.'/*.*');
        foreach ($add_files as $add_file) {
            $file_to_copy = str_replace($src, '', $add_file);
            Log::debug('Copying: '.$file_to_copy);
            try {
                Storage::disk('public')->put($dst.$file_to_copy, file_get_contents($src.$file_to_copy));
            } catch (\Exception $e) {
                Log::debug($e);
            }
        }
    }
}
