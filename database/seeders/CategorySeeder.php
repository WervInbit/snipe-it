<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CategorySeeder extends Seeder
{
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        Category::truncate();
        Schema::enableForeignKeyConstraints();

        $admin = User::where('permissions->superuser', '1')->first() ?? User::factory()->firstAdmin()->create();

        Category::factory()->assetLaptopCategory()->create([
            'created_by' => $admin->id,
            'name' => 'Laptops',
        ]);

        Category::factory()->assetTabletCategory()->create([
            'created_by' => $admin->id,
            'name' => 'Tablets',
        ]);

        Category::factory()->assetMobileCategory()->create([
            'created_by' => $admin->id,
            'name' => 'Mobile Phones',
        ]);
    }
}
