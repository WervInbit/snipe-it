<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    public function run()
    {
        Supplier::query()->delete();
        DB::statement('ALTER TABLE suppliers AUTO_INCREMENT = 1');
        Supplier::factory()->count(5)->create();
    }
}
