<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestResult;
use App\Models\TestType;
use App\Models\User;
use App\Models\WorkflowProfile;
use App\Models\WorkflowProfileItem;
use App\Services\WorkflowRunDefinitionService;
use Tests\TestCase;

class AllTestsPassedIndicatorTest extends TestCase
{
    public function testIndicatorShownWhenAllTestsPass(): void
    {
        $asset = Asset::factory()->create();
        $testType = TestType::factory()->create(['applies_to_all' => true]);
        $profile = WorkflowProfile::factory()->create();
        $profileItem = WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $profile->id,
            'workflow_item_id' => $testType->id,
        ]);
        $hash = app(WorkflowRunDefinitionService::class)
            ->forProfile($asset, $profile)['readiness_context_hash'];
        $user = User::factory()->refurbisher()->viewAssets()->create();
        $run = TestRun::factory()->for($asset)->for($user)->create([
            'workflow_profile_id' => $profile->id,
            'readiness_context_hash' => $hash,
        ]);
        $result = TestResult::factory()->create([
            'workflow_run_id' => $run->id,
            'workflow_item_id' => $testType->id,
            'workflow_profile_item_id' => $profileItem->id,
            'status' => TestResult::STATUS_FAIL,
        ]);

        $asset->refreshTestCompletionFlag();
        $this->assertFalse($asset->tests_completed_ok);

        $this->actingAs($user)
            ->put(route('test-results.update', ['asset' => $asset->id, 'testRun' => $run->id]), [
                'status' => [
                    $result->id => TestResult::STATUS_PASS,
                ],
            ])
            ->assertRedirect(route('test-runs.index', $asset->id));

        $asset->refresh();
        $this->assertTrue($asset->tests_completed_ok);

        $this->actingAs($user)
            ->get(route('hardware.show', $asset))
            ->assertSee(trans('tests.all_passed'));
    }

    public function testIndicatorHiddenWhenFailuresExist(): void
    {
        $asset = Asset::factory()->create();
        $testType = TestType::factory()->create(['applies_to_all' => true]);
        $profile = WorkflowProfile::factory()->create();
        $profileItem = WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $profile->id,
            'workflow_item_id' => $testType->id,
        ]);
        $hash = app(WorkflowRunDefinitionService::class)
            ->forProfile($asset, $profile)['readiness_context_hash'];
        $run = TestRun::factory()->create([
            'asset_id' => $asset->id,
            'workflow_profile_id' => $profile->id,
            'readiness_context_hash' => $hash,
        ]);
        TestResult::factory()->create([
            'workflow_run_id' => $run->id,
            'status' => TestResult::STATUS_FAIL,
            'workflow_item_id' => $testType->id,
            'workflow_profile_item_id' => $profileItem->id,
        ]);

        $asset->refreshTestCompletionFlag();
        $this->assertFalse($asset->tests_completed_ok);

        $user = User::factory()->viewAssets()->create();

        $this->actingAs($user)
            ->get(route('hardware.show', $asset))
            ->assertOk();
    }
}
