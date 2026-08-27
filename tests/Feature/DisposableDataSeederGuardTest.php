<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Database\Seeders\DemoAssetsSeeder;
use Database\Seeders\DevelopmentDeviceScenarioSeeder;
use Database\Seeders\ProductionDemoUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use LogicException;
use Tests\TestCase;

class DisposableDataSeederGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_disposable_seeders_require_explicit_opt_in_even_while_testing(): void
    {
        Config::set('demo.allow_disposable_data_seeding', false);
        $canary = Asset::factory()->create();

        $this->assertSeederBlocked(DemoAssetsSeeder::class, 'requires SNIPEIT_ALLOW_DISPOSABLE_DATA_SEEDING=true');
        $this->assertSeederBlocked(DevelopmentDeviceScenarioSeeder::class, 'requires SNIPEIT_ALLOW_DISPOSABLE_DATA_SEEDING=true');
        $this->assertSeederBlocked(ProductionDemoUserSeeder::class, 'requires SNIPEIT_ALLOW_DISPOSABLE_DATA_SEEDING=true');

        $this->assertDatabaseHas('assets', ['id' => $canary->id]);
        $this->assertDatabaseMissing('users', ['username' => 'demo_admin']);
    }

    public function test_disposable_seeders_remain_blocked_outside_local_and_testing_with_opt_in(): void
    {
        Config::set('demo.allow_disposable_data_seeding', true);
        $originalEnvironment = $this->app->environment();
        $this->app['env'] = 'production';
        $canary = Asset::factory()->create();

        try {
            $this->assertSeederBlocked(DemoAssetsSeeder::class, 'restricted to local/testing environments');
            $this->assertSeederBlocked(DevelopmentDeviceScenarioSeeder::class, 'restricted to local/testing environments');
            $this->assertSeederBlocked(ProductionDemoUserSeeder::class, 'restricted to local/testing environments');
        } finally {
            $this->app['env'] = $originalEnvironment;
        }

        $this->assertDatabaseHas('assets', ['id' => $canary->id]);
        $this->assertDatabaseMissing('users', ['username' => 'demo_admin']);
    }

    public function test_demo_user_seeder_runs_with_explicit_disposable_opt_in(): void
    {
        Config::set('demo.allow_disposable_data_seeding', true);

        $this->app->make(ProductionDemoUserSeeder::class)->run();

        $admin = User::query()->where('username', 'admin')->firstOrFail();

        $this->assertTrue($admin->isSuperUser());
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertDatabaseHas('users', [
            'username' => 'demo_admin',
            'activated' => 1,
        ]);
    }

    /**
     * @param class-string $seederClass
     */
    private function assertSeederBlocked(string $seederClass, string $expectedMessage): void
    {
        try {
            $this->app->make($seederClass)->run();
            $this->fail($seederClass.' should have been blocked.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString($expectedMessage, $exception->getMessage());
        }
    }
}
