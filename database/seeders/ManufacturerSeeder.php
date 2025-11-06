<?php

namespace Database\Seeders;

use App\Models\Manufacturer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class ManufacturerSeeder extends Seeder
{
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        Manufacturer::truncate();
        Schema::enableForeignKeyConstraints();

        $admin = User::where('permissions->superuser', '1')->first() ?? User::factory()->firstAdmin()->create();

        Manufacturer::factory()->apple()->create(['created_by' => $admin->id]);
        Manufacturer::factory()->dell()->create(['created_by' => $admin->id]);
        Manufacturer::factory()->hp()->create(['created_by' => $admin->id]);
        Manufacturer::factory()->lenovo()->create(['created_by' => $admin->id]);
        Manufacturer::factory()->samsung()->create(['created_by' => $admin->id]);
        Manufacturer::factory()->google()->create(['created_by' => $admin->id]);

        $src = public_path('/img/demo/manufacturers/');
        $dst = 'manufacturers'.'/';
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
