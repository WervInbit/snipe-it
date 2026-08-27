<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardsDisposableDataSeeding;
use Illuminate\Database\Seeder;

abstract class DestructiveFixtureSeeder extends Seeder
{
    use GuardsDisposableDataSeeding;

    final public function run(): void
    {
        $this->assertDisposableDataSeedingAllowed();
        $this->seedFixtures();
    }

    abstract protected function seedFixtures(): void;
}
