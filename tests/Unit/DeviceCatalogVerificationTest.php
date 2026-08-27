<?php

namespace Tests\Unit;

use Database\Seeders\Concerns\ProvidesDeviceCatalogData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DeviceCatalogVerificationTest extends TestCase
{
    public function test_every_shared_model_blueprint_has_explicit_verification_metadata(): void
    {
        $blueprints = $this->catalogProbe()->allBlueprints();
        $statuses = collect($blueprints)->pluck('model_number_verification');

        $this->assertCount(12, $blueprints);
        $this->assertNotContains(null, $statuses->all(), true);
        $this->assertEmpty(
            $statuses->diff([
                'verified_catalog_identifier',
                'unverified_demo_placeholder',
            ])->all()
        );
        $this->assertEqualsCanonicalizing(
            [
                'HP ProBook 430 G3',
                'Microsoft Surface Pro 4',
                'Microsoft Surface Pro 5',
                'iPhone 12',
                'Pixel 8 Pro',
            ],
            collect($blueprints)
                ->filter(fn (array $config): bool => (
                    $config['model_number_verification'] ?? null
                ) === 'unverified_demo_placeholder')
                ->keys()
                ->all()
        );
    }

    public function test_normal_seed_context_fails_closed_to_verified_identifiers(): void
    {
        Config::set('demo.allow_disposable_data_seeding', false);

        $codes = collect($this->catalogProbe()->seedableBlueprints())
            ->pluck('code')
            ->all();

        $this->assertContains('2E9F8EA#ABH', $codes);
        $this->assertContains('SM-A520F', $codes);
        $this->assertNotContains('PIXEL8PRO-256-OBSIDIAN', $codes);
        $this->assertNotContains('MS-SURFPRO4-I5-4-128', $codes);
    }

    public function test_non_disposable_environment_cannot_enable_demo_placeholders(): void
    {
        $originalEnvironment = app()->environment();
        Config::set('demo.allow_disposable_data_seeding', true);
        app()->detectEnvironment(static fn (): string => 'production');

        try {
            $codes = collect($this->catalogProbe()->seedableBlueprints())
                ->pluck('code')
                ->all();

            $this->assertNotContains('PIXEL8PRO-256-OBSIDIAN', $codes);
            $this->assertNotContains('MS-SURFPRO4-I5-4-128', $codes);
        } finally {
            app()->detectEnvironment(static fn (): string => $originalEnvironment);
        }
    }

    private function catalogProbe(): DeviceCatalogVerificationProbe
    {
        return new DeviceCatalogVerificationProbe();
    }
}

final class DeviceCatalogVerificationProbe extends Seeder
{
    use ProvidesDeviceCatalogData;

    /**
     * @return array<string,array<string,mixed>>
     */
    public function allBlueprints(): array
    {
        return $this->modelBlueprints();
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function seedableBlueprints(): array
    {
        return $this->seedableModelBlueprints();
    }
}
