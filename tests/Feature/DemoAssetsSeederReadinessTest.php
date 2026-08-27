<?php

namespace Tests\Feature;

use App\Models\Asset;
use Database\Seeders\DemoAssetsSeeder;
use Database\Seeders\ProductionDemoUserSeeder;
use Database\Seeders\ProductionFoundationSeeder;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DemoAssetsSeederReadinessTest extends TestCase
{
    public function test_demo_assets_do_not_seed_retired_assignment_or_lifecycle_metadata(): void
    {
        Config::set('demo.allow_disposable_data_seeding', true);

        $this->seed(ProductionFoundationSeeder::class);
        $this->seed(ProductionDemoUserSeeder::class);
        $this->seed(DemoAssetsSeeder::class);

        $demoAssets = Asset::query()
            ->where('asset_tag', 'like', 'DEMO-%')
            ->orderBy('asset_tag')
            ->get();

        $this->assertNotEmpty($demoAssets);

        foreach ($demoAssets as $asset) {
            foreach ([
                'assigned_to',
                'assigned_type',
                'accepted',
                'last_checkin',
                'last_checkout',
                'expected_checkin',
                'last_audit_date',
                'next_audit_date',
            ] as $field) {
                $this->assertNull(
                    $asset->getRawOriginal($field),
                    "{$asset->asset_tag} seeded retired field [{$field}].",
                );
            }

            $this->assertFalse(
                (bool) $asset->getRawOriginal('requestable'),
                "{$asset->asset_tag} was seeded as requestable.",
            );
        }
    }

    public function test_sale_lifecycle_demo_assets_receive_current_complete_readiness_runs(): void
    {
        Config::set('demo.allow_disposable_data_seeding', true);

        $this->seed(ProductionFoundationSeeder::class);
        $this->seed(ProductionDemoUserSeeder::class);
        $this->seed(DemoAssetsSeeder::class);

        foreach ([
            'DEMO-001' => 'Ready for Sale',
            'DEMO-004' => 'Ready for Sale',
            'DEMO-010' => 'Sold',
        ] as $assetTag => $expectedStatus) {
            $asset = Asset::query()->where('asset_tag', $assetTag)->firstOrFail();
            $run = $asset->tests()->firstOrFail();

            $this->assertSame($expectedStatus, $asset->assetstatus?->name, $assetTag);
            $this->assertMatchesRegularExpression(
                '/^[a-f0-9]{64}$/',
                (string) $run->readiness_context_hash,
                $assetTag
            );
            $this->assertTrue((bool) $asset->tests_completed_ok, $assetTag);
            $this->assertTrue($asset->liveTestsCompletedOk(), $assetTag);
        }
    }
}
