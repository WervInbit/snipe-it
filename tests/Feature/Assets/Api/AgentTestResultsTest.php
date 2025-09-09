<?php

namespace Tests\Feature\Assets\Api;

use App\Models\Asset;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\TestType;
use Tests\TestCase;

class AgentTestResultsTest extends TestCase
{
    public function test_agent_can_submit_test_results(): void
    {
        \App\Models\User::factory()->create();
        $asset = Asset::factory()->laptopMbp()->create(['asset_tag' => 'TAG123']);
        $cpu = TestType::factory()->create(['slug' => 'cpu']);
        $ram = TestType::factory()->create(['slug' => 'ram']);

        $payload = [
            'asset_tag' => $asset->asset_tag,
            'results' => [
                [
                    'test_slug' => $cpu->slug,
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
            'test_type_id' => $cpu->id,
            'status' => TestResult::STATUS_PASS,
            'note' => 'All good',
        ]);

        $this->assertDatabaseHas('test_results', [
            'test_run_id' => $run->id,
            'test_type_id' => $ram->id,
            'status' => TestResult::STATUS_NVT,
            'note' => 'Not tested by agent',
        ]);

        $asset->refresh();
        $this->assertTrue((bool) $asset->tests_completed_ok);
    }

    public function test_agent_gets_404_for_unknown_asset_tag(): void
    {
        \App\Models\User::factory()->create();
        $type = TestType::factory()->create(['slug' => 'cpu']);

        $payload = [
            'asset_tag' => 'MISSING_TAG',
            'results' => [
                [
                    'test_slug' => $type->slug,
                    'status' => TestResult::STATUS_PASS,
                ],
            ],
        ];

        config(['agent.api_token' => 'secrettoken']);

        $this->postJson('/api/v1/agent/test-results', $payload, [
            'Authorization' => 'Bearer secrettoken',
        ])->assertStatus(404);
    }
}
