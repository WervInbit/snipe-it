<?php

namespace Tests\Feature\Assets\Api;

use App\Models\Asset;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\TestType;
use Illuminate\Support\Facades\Log;
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

        Log::spy();

        $this->postJson('/api/v1/agent/test-results', $payload, [
            'Authorization' => 'Bearer secrettoken',
        ])->assertStatus(200)
            ->assertJsonStructure(['message', 'test_run_id']);

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

        Log::shouldHaveReceived('info')->once()->withArgs(function ($message) use ($asset) {
            return str_contains($message, $asset->asset_tag) && str_contains($message, '127.0.0.1');
        });
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
        ])->assertStatus(404)
            ->assertJson(['message' => 'Asset not found']);
    }

    public function test_agent_gets_400_for_validation_errors(): void
    {
        \App\Models\User::factory()->create();
        $asset = Asset::factory()->laptopMbp()->create(['asset_tag' => 'TAG999']);
        TestType::factory()->create(['slug' => 'cpu']);

        $payload = [
            'asset_tag' => $asset->asset_tag,
            'results' => [
                [
                    'test_slug' => 'cpu',
                    'status' => 'bad-status',
                ],
            ],
        ];

        config(['agent.api_token' => 'secrettoken']);

        $this->postJson('/api/v1/agent/test-results', $payload, [
            'Authorization' => 'Bearer secrettoken',
        ])->assertStatus(400)
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_agent_gets_401_if_ip_not_allowed(): void
    {
        \App\Models\User::factory()->create();
        $asset = Asset::factory()->laptopMbp()->create(['asset_tag' => 'TAGIP1']);
        $cpu = TestType::factory()->create(['slug' => 'cpu']);

        $payload = [
            'asset_tag' => $asset->asset_tag,
            'results' => [
                [
                    'test_slug' => $cpu->slug,
                    'status' => TestResult::STATUS_PASS,
                ],
            ],
        ];

        config(['agent.api_token' => 'secrettoken']);
        config(['agent.allowed_ips' => ['10.0.0.1']]);

        $this->postJson('/api/v1/agent/test-results', $payload, [
            'Authorization' => 'Bearer secrettoken',
        ])->assertStatus(401)
            ->assertJson(['message' => 'Unauthorized']);
    }
}
