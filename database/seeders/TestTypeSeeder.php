<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TestType;

class TestTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'External Cleaning', 'description' => 'External surfaces cleaned'],
            ['name' => 'Internal Cleaning', 'description' => 'Internal components cleaned'],
        ];

        foreach ($types as $type) {
            TestType::firstOrCreate(['name' => $type['name']], ['description' => $type['description']]);
        }
    }
}
