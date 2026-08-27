<?php

namespace Tests\Feature;

use Database\Seeders\AccessorySeeder;
use Database\Seeders\ActionlogSeeder;
use Database\Seeders\AssetModelSeeder;
use Database\Seeders\AssetSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CompanySeeder;
use Database\Seeders\ComponentSeeder;
use Database\Seeders\Concerns\GuardsDisposableDataSeeding;
use Database\Seeders\ConsumableSeeder;
use Database\Seeders\CustomFieldSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\DepreciationSeeder;
use Database\Seeders\DestructiveFixtureSeeder;
use Database\Seeders\LicenseSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\ManufacturerSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StatuslabelSeeder;
use Database\Seeders\SupplierSeeder;
use Database\Seeders\UserSeeder;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LegacyDestructiveFixtureSeederGuardTest extends TestCase
{
    #[DataProvider('destructiveFixtureSeeders')]
    public function test_legacy_fixture_seeder_is_blocked_without_explicit_opt_in(string $seederClass): void
    {
        config()->set('demo.allow_disposable_data_seeding', false);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('requires SNIPEIT_ALLOW_DISPOSABLE_DATA_SEEDING=true');

        $this->app->make($seederClass)->run();
    }

    public function test_destructive_fixture_base_remains_blocked_in_production_with_opt_in(): void
    {
        config()->set('demo.allow_disposable_data_seeding', true);
        $originalEnvironment = $this->app->environment();
        $this->app['env'] = 'production';

        $seeder = new class extends DestructiveFixtureSeeder
        {
            protected function seedFixtures(): void
            {
                throw new \RuntimeException('The guarded fixture body must not run.');
            }
        };

        try {
            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('restricted to local/testing environments');
            $seeder->run();
        } finally {
            $this->app['env'] = $originalEnvironment;
        }
    }

    public function test_broadly_destructive_seeder_sources_use_a_disposable_guard(): void
    {
        $pattern = '/::truncate\s*\(|->truncate\s*\(|::query\(\)->delete\s*\(|DB::table\([^;]+?\)->delete\s*\(/s';

        foreach (glob(database_path('seeders/*Seeder.php')) as $path) {
            $source = file_get_contents($path);

            if (! preg_match($pattern, $source)) {
                continue;
            }

            $class = 'Database\\Seeders\\'.pathinfo($path, PATHINFO_FILENAME);
            $usesGuardTrait = in_array(
                GuardsDisposableDataSeeding::class,
                class_uses_recursive($class),
                true
            );

            $this->assertTrue(
                is_subclass_of($class, DestructiveFixtureSeeder::class) || $usesGuardTrait,
                $class.' performs broad destructive writes without a disposable-data guard.'
            );
        }
    }

    /**
     * @return array<string,array{class-string<DestructiveFixtureSeeder>}>
     */
    public static function destructiveFixtureSeeders(): array
    {
        return collect([
            AccessorySeeder::class,
            ActionlogSeeder::class,
            AssetModelSeeder::class,
            AssetSeeder::class,
            CategorySeeder::class,
            CompanySeeder::class,
            ComponentSeeder::class,
            ConsumableSeeder::class,
            CustomFieldSeeder::class,
            DepartmentSeeder::class,
            DepreciationSeeder::class,
            LicenseSeeder::class,
            LocationSeeder::class,
            ManufacturerSeeder::class,
            RolePermissionSeeder::class,
            StatuslabelSeeder::class,
            SupplierSeeder::class,
            UserSeeder::class,
        ])->mapWithKeys(
            fn (string $class): array => [$class => [$class]]
        )->all();
    }
}
