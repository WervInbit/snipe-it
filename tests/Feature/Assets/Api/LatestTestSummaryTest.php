<?php

namespace Tests\Feature\Assets\Api;

use App\Models\Asset;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\TestType;
use App\Models\User;
use App\Models\WorkflowProfile;
use App\Models\WorkflowProfileItem;
use App\Services\WorkflowRunDefinitionService;
use Tests\TestCase;

class LatestTestSummaryTest extends TestCase
{
    public function testSummaryUsesBlockingWorkflowProfilesInsteadOfNewestRunOnly(): void
    {
        $asset = Asset::factory()->create();
        $battery = TestType::factory()->create(['name' => 'Battery', 'applies_to_all' => true]);
        $shippingCheck = TestType::factory()->create(['name' => 'Shipping Check', 'applies_to_all' => true]);
        $diagnostics = WorkflowProfile::factory()->create([
            'name' => 'Diagnostics',
            'blocks_sale_readiness' => true,
        ]);
        $shipping = WorkflowProfile::factory()->create([
            'name' => 'Shipping',
            'blocks_sale_readiness' => false,
        ]);
        $diagnosticProfileItem = WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $diagnostics->id,
            'workflow_item_id' => $battery->id,
            'is_required' => true,
        ]);
        WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $shipping->id,
            'workflow_item_id' => $shippingCheck->id,
            'is_required' => true,
        ]);
        $diagnosticHash = app(WorkflowRunDefinitionService::class)
            ->forProfile($asset, $diagnostics)['readiness_context_hash'];

        $diagnosticRun = TestRun::factory()->create([
            'asset_id' => $asset->id,
            'workflow_profile_id' => $diagnostics->id,
            'profile_name_snapshot' => $diagnostics->name,
            'readiness_context_hash' => $diagnosticHash,
            'created_at' => now()->subDay(),
            'finished_at' => now()->subDay(),
        ]);
        TestResult::factory()->create([
            'workflow_run_id' => $diagnosticRun->id,
            'workflow_item_id' => $battery->id,
            'workflow_profile_item_id' => $diagnosticProfileItem->id,
            'status' => TestResult::STATUS_FAIL,
            'is_required' => true,
        ]);

        $shippingRun = TestRun::factory()->create([
            'asset_id' => $asset->id,
            'workflow_profile_id' => $shipping->id,
            'profile_name_snapshot' => $shipping->name,
            'created_at' => now(),
            'finished_at' => now(),
        ]);
        TestResult::factory()->create([
            'workflow_run_id' => $shippingRun->id,
            'workflow_item_id' => $shippingCheck->id,
            'status' => TestResult::STATUS_PASS,
            'is_required' => true,
        ]);

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(route('api.assets.latest-test-summary', $asset))
            ->assertOk()
            ->assertJsonPath('run_id', $diagnosticRun->id)
            ->assertJsonPath('run_label', 'Diagnostics')
            ->assertJsonPath('failed_count', 1)
            ->assertJsonPath('missing_count', 0)
            ->assertJsonPath('failed.0.label', 'Diagnostics: Battery');
    }

    public function testSummaryReportsMissingBlockingWorkflowProfileNames(): void
    {
        $asset = Asset::factory()->create();
        $diagnosticItem = TestType::factory()->create(['name' => 'Diagnostic', 'applies_to_all' => true]);
        $photoItem = TestType::factory()->create(['name' => 'Sale Photos', 'applies_to_all' => true]);
        $diagnostics = WorkflowProfile::factory()->create([
            'name' => 'Diagnostics',
            'blocks_sale_readiness' => true,
        ]);
        $salePhotos = WorkflowProfile::factory()->create([
            'name' => 'Sale Photos',
            'blocks_sale_readiness' => true,
        ]);
        $diagnosticProfileItem = WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $diagnostics->id,
            'workflow_item_id' => $diagnosticItem->id,
            'is_required' => true,
        ]);
        WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $salePhotos->id,
            'workflow_item_id' => $photoItem->id,
            'is_required' => true,
        ]);
        $diagnosticHash = app(WorkflowRunDefinitionService::class)
            ->forProfile($asset, $diagnostics)['readiness_context_hash'];
        $run = TestRun::factory()->create([
            'asset_id' => $asset->id,
            'workflow_profile_id' => $diagnostics->id,
            'profile_name_snapshot' => $diagnostics->name,
            'readiness_context_hash' => $diagnosticHash,
        ]);
        TestResult::factory()->create([
            'workflow_run_id' => $run->id,
            'workflow_item_id' => $diagnosticItem->id,
            'workflow_profile_item_id' => $diagnosticProfileItem->id,
            'status' => TestResult::STATUS_PASS,
            'is_required' => true,
        ]);

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(route('api.assets.latest-test-summary', $asset))
            ->assertOk()
            ->assertJsonPath('missing_count', 1)
            ->assertJsonPath('missing.0.label', 'Sale Photos')
            ->assertJsonPath('missing.0.type', 'workflow_profile')
            ->assertJsonPath('missing_profiles.0', 'Sale Photos');
    }
}
