<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestResult;
use App\Models\TestType;
use App\Models\User;
use App\Models\WorkflowProfile;
use App\Models\WorkflowProfileItem;
use App\Services\WorkflowRunDefinitionService;
use Tests\TestCase;

class SaveTestRunResultsTest extends TestCase
{
    public function test_run_can_be_saved_and_marked_complete(): void
    {
        $asset = Asset::factory()->create();
        $type = TestType::factory()->create(['applies_to_all' => true]);
        $profile = WorkflowProfile::factory()->create();
        $profileItem = WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $profile->id,
            'workflow_item_id' => $type->id,
        ]);
        $hash = app(WorkflowRunDefinitionService::class)
            ->forProfile($asset, $profile)['readiness_context_hash'];
        $user = User::factory()->refurbisher()->create();
        $run = TestRun::factory()->for($asset)->for($user)->create([
            'workflow_profile_id' => $profile->id,
            'readiness_context_hash' => $hash,
            'finished_at' => now()->subDay(),
        ]);
        $oldFinished = $run->finished_at;
        $result = TestResult::factory()->for($run)->for($type, 'type')
            ->create([
                'workflow_profile_item_id' => $profileItem->id,
                'status' => TestResult::STATUS_NVT,
            ]);

        $response = $this->actingAs($user)->put(
            route('test-results.update', [$asset->id, $run->id]),
            [
                'status' => [$result->id => TestResult::STATUS_PASS],
                'note' => [$result->id => 'looks good'],
            ]
        );

        $response->assertRedirect(route('test-runs.index', $asset->id));
        $response->assertSessionHas('success');

        $run->refresh();
        $this->assertTrue($run->finished_at->gt($oldFinished));

        $result->refresh();
        $this->assertEquals(TestResult::STATUS_PASS, $result->status);
        $this->assertEquals('looks good', $result->note);

        $asset->refresh();
        $this->assertTrue($asset->tests_completed_ok);
    }
}

