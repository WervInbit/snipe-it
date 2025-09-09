<?php

namespace Tests\Feature\Assets\Api;

use App\Models\Asset;
use App\Models\Setting;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\TestType;
use Tests\TestCase;

class AgentTestResultsTest extends TestCase
{
    public function test_agent_can_submit_test_results(): void
    {
        Setting::factory()->create();

        $asset = Asset::factory()->laptopMbp()->create(['asset_tag' => 'TAG123']);
        $type = TestType::factory()->create(['slug' => 'cpu']);

        $payload = [
            'asset_tag' => $asset->asset_tag,
            'results' => [
                [
                    'test_slug' => $type->slug,
                    'status' => TestResult::STATUS_PASS,
                    'note' => 'All good',
                ],
            ],
        ];

        config(['agent.api_token' => 'secrettoken']);

        $this->postJson('/api/v1/agent/test-results', $payload, [
            'Authorization' => 'Bearer secrettoken',
        ])->assertStatus(201);

        $run = TestRun::where('asset_id', $asset->id)->first();
        $this->assertNotNull($run);

        $this->assertDatabaseHas('test_results', [
            'test_run_id' => $run->id,
            'test_type_id' => $type->id,
            'status' => TestResult::STATUS_PASS,
            'note' => 'All good',
        ]);

        $asset->refresh();
        $this->assertTrue((bool) $asset->tests_completed_ok);
    }
}
