<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\ComponentEvent;
use App\Models\ComponentInstance;
use App\Services\Components\AssetComponentRosterService;
use Database\Seeders\DevelopmentDeviceScenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevelopmentDeviceScenarioSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_development_device_scenario_seeder_creates_rerunnable_component_scenarios(): void
    {
        $this->assertSame(0, Asset::query()->where('asset_tag', 'like', 'DEV-COMP-%')->count());

        $this->seed(DevelopmentDeviceScenarioSeeder::class);

        $this->assertSame(4, Asset::query()->where('asset_tag', 'like', 'DEV-COMP-%')->count());

        $baseline = Asset::query()->where('asset_tag', 'DEV-COMP-001')->firstOrFail();
        $complexLaptop = Asset::query()->where('asset_tag', 'DEV-COMP-002')->firstOrFail();
        $phone = Asset::query()->where('asset_tag', 'DEV-COMP-003')->firstOrFail();
        $tablet = Asset::query()->where('asset_tag', 'DEV-COMP-004')->firstOrFail();

        $this->assertSame(0, ComponentInstance::query()->where('current_asset_id', $baseline->id)->count());
        $this->assertGreaterThan(0, ComponentInstance::query()->where('current_asset_id', $complexLaptop->id)->count());
        $this->assertGreaterThan(0, ComponentInstance::query()->where('current_asset_id', $phone->id)->count());
        $this->assertGreaterThan(0, ComponentInstance::query()->where('source_asset_id', $tablet->id)->whereNull('current_asset_id')->count());

        $complexRoster = app(AssetComponentRosterService::class)->buildForAsset($complexLaptop);
        $complexClassifications = $complexRoster->rows
            ->pluck('classification')
            ->unique()
            ->values()
            ->all();

        $this->assertContains('expected', $complexClassifications);
        $this->assertContains('expected_tracked', $complexClassifications);
        $this->assertContains('extra', $complexClassifications);
        $this->assertContains('custom', $complexClassifications);

        $phoneRoster = app(AssetComponentRosterService::class)->buildForAsset($phone);
        $phoneClassifications = $phoneRoster->rows
            ->pluck('classification')
            ->unique()
            ->values()
            ->all();

        $this->assertContains('removed', $phoneClassifications);
        $this->assertContains('extra', $phoneClassifications);

        $this->assertTrue(
            ComponentInstance::query()
                ->where('current_asset_id', $complexLaptop->id)
                ->whereNotNull('parent_component_instance_id')
                ->where('condition_code', ComponentInstance::CONDITION_BROKEN)
                ->exists()
        );
        $this->assertTrue(
            ComponentInstance::query()
                ->where('source_asset_id', $complexLaptop->id)
                ->whereNull('current_asset_id')
                ->whereNotNull('ancestry_parent_component_instance_id')
                ->where('metadata_json', 'like', '%complex-laptop-removed-child%')
                ->exists()
        );
        $this->assertTrue(
            ComponentInstance::query()
                ->where('current_asset_id', $complexLaptop->id)
                ->whereNull('component_definition_id')
                ->where('display_name', 'DEV Custom Unknown Daughterboard')
                ->exists()
        );
        $this->assertTrue(
            ComponentInstance::query()
                ->where('display_name', 'DEV Loose Tray RAM')
                ->where('lifecycle_status', ComponentInstance::LIFECYCLE_IN_TRAY)
                ->whereNotNull('held_by_user_id')
                ->exists()
        );
        $this->assertGreaterThanOrEqual(
            4,
            ComponentInstance::query()
                ->whereNull('current_asset_id')
                ->where('metadata_json', 'like', '%loose-%')
                ->count()
        );
        $this->assertGreaterThan(0, ComponentEvent::query()->count());
        $this->assertGreaterThan(0, ComponentInstance::query()->whereNotNull('qr_uid')->count());

        $expectedTags = Asset::query()
            ->where('asset_tag', 'like', 'DEV-COMP-%')
            ->orderBy('asset_tag')
            ->pluck('asset_tag')
            ->all();

        $this->seed(DevelopmentDeviceScenarioSeeder::class);

        $this->assertSame(
            $expectedTags,
            Asset::query()
                ->where('asset_tag', 'like', 'DEV-COMP-%')
                ->orderBy('asset_tag')
                ->pluck('asset_tag')
                ->all()
        );
        $this->assertSame(1, Asset::query()->where('asset_tag', 'DEV-COMP-002')->count());
    }
}
