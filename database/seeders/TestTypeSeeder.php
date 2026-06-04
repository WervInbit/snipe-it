<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Backward-compatible entry point for older seed flows.
 */
class TestTypeSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AttributeTestSeeder::class);
    }
}
