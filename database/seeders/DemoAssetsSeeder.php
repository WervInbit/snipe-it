<?php

namespace Database\Seeders;

use App\Models\Asset;
use Illuminate\Database\Seeder;

class DemoAssetsSeeder extends Seeder
{
    public function run()
    {
        for ($i = 1; $i <= 5; $i++) {
            Asset::factory()->create([
                'asset_tag' => sprintf('ASSET-%04d', $i),
                'name' => 'Demo Asset ' . $i,
            ]);
        }
    }
}
