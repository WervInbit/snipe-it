<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class SupplierSeeder extends Seeder
{
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        Supplier::truncate();
        Schema::enableForeignKeyConstraints();

        Supplier::factory()->create([
            'name' => 'TechCycle Partners',
        ]);

        Supplier::factory()->create([
            'name' => 'Renewed Supply Co.',
        ]);
    }
}
