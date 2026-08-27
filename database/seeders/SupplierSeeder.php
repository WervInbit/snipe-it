<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Support\Facades\Schema;

class SupplierSeeder extends DestructiveFixtureSeeder
{
    protected function seedFixtures(): void
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
