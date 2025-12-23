<?php

namespace Tests\Feature\Assets\Api;

use App\Models\Asset;
use App\Models\AttributeDefinition;
use App\Models\TestResult;
use App\Models\TestRun;
use Database\Seeders\DeviceAttributeSeeder;
use Database\Seeders\DevicePresetSeeder;
use Database\Seeders\AttributeTestSeeder;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AgentTestResultsTest extends TestCase
{
    protected string $keyboardSlug;
    protected string $wifiSlug;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DeviceAttributeSeeder::class);
        $this->seed(AttributeTestSeeder::class);
        $this->seed(DevicePresetSeeder::class);

        $keyboardDefinition = AttributeDefinition::where('key', 'keyboard')->firstOrFail()->loadMissing('tests');
        $wifiDefinition = AttributeDefinition::where('key', 'wifi')->firstOrFail()->loadMissing('tests');

        $this->keyboardSlug = $keyboardDefinition->tests->firstWhere('slug', 'keyboard')?->slug
            ?? $keyboardDefinition->tests->first()?->slug;
        $this->wifiSlug = $wifiDefinition->tests->firstWhere('slug', 'wifi')?->slug
            ?? $wifiDefinition->tests->first()?->slug;

        $this->assertNotNull($this->keyboardSlug, 'Keyboard test slug missing');
        $this->assertNotNull($this->wifiSlug, 'Wi-Fi test slug missing');
    }

    public function test_agent_can_submit_test_results(): void
    {
        \App\Models\User::factory()->create();
        $agent = \App\Models\User::factory()->create();
        $asset = Asset::factory()->laptopMbp()->create(['asset_tag' => 'TAG123']);

        $payload = [
            'type' => 'test_results',
            'asset_tag' => $asset->asset_tag,
            'results' => [
                [
                    'test_slug' => $this->keyboardSlug,
                    'status' => TestResult::STATUS_PASS,
                    'note' => 'All good',
                ],
            ],
        ];

        config([
            'agent.api_token' => 'secrettoken',
            'agent.user_id' => $agent->id,
        ]);

        Log::spy();

        $this->postJson('/api/v1/agent/reports', $payload, [
            'Authorization' => 'Bearer secrettoken',
        ])->assertStatus(200)
            ->assertJsonStructure(['message', 'test_run_id']);

        $run = TestRun::where('asset_id', $asset->id)->first();
        $this->assertNotNull($run);
        $this->assertEquals($agent->id, $run->user_id);

        $this->assertDatabaseHas('test_results', [
            'test_run_id' => $run->id,
            'test_type_id' => $run->results()->whereHas('type', fn ($query) => $query->where('slug', $this->keyboardSlug))->value('test_type_id'),
            'status' => TestResult::STATUS_PASS,
            'note' => 'All good',
        ]);

        $wifiResult = $run->results()->whereHas('type', fn ($query) => $query->where('slug', $this->wifiSlug))->first();
        $this->assertNotNull($wifiResult);

        $this->assertEquals(TestResult::STATUS_NVT, $wifiResult->status);
        $this->assertEquals('Not tested by agent', $wifiResult->note);

        $this->assertDatabaseHas('test_results', [
            'test_run_id' => $run->id,
            'test_type_id' => $wifiResult->test_type_id,
            'status' => TestResult::STATUS_NVT,
        ]);

        $asset->refresh();
        $this->assertTrue((bool) $asset->tests_completed_ok);

        $this->assertDatabaseHas('test_audits', [
            'auditable_type' => TestRun::class,
            'auditable_id' => $run->id,
            'user_id' => $agent->id,
        ]);

        $keyboardResult = TestResult::where('test_run_id', $run->id)
            ->whereHas('type', fn ($query) => $query->where('slug', $this->keyboardSlug))
            ->first();
        $this->assertDatabaseHas('test_audits', [
            'auditable_type' => TestResult::class,
            'auditable_id' => $keyboardResult->id,
            'user_id' => $agent->id,
        ]);

        Log::shouldHaveReceived('info')->once()->withArgs(function ($message) use ($asset) {
            return str_contains($message, $asset->asset_tag) && str_contains($message, '127.0.0.1');
        });
    }

    public function test_agent_gets_404_for_unknown_asset_tag(): void
    {
        \App\Models\User::factory()->create();

        $payload = [
            'type' => 'test_results',
            'asset_tag' => 'MISSING_TAG',
            'results' => [
                [
                    'test_slug' => $this->keyboardSlug,
                    'status' => TestResult::STATUS_PASS,
                ],
            ],
        ];

        config(['agent.api_token' => 'secrettoken']);

        $this->postJson('/api/v1/agent/reports', $payload, [
            'Authorization' => 'Bearer secrettoken',
        ])->assertStatus(404)
            ->assertJson(['message' => 'Asset not found']);
    }

    public function test_agent_gets_400_for_validation_errors(): void
    {
        \App\Models\User::factory()->create();
        $asset = Asset::factory()->laptopMbp()->create(['asset_tag' => 'TAG999']);

        $payload = [
            'type' => 'test_results',
            'asset_tag' => $asset->asset_tag,
            'results' => [
                [
                    'test_slug' => $this->keyboardSlug,
                    'status' => 'bad-status',
                ],
            ],
        ];

        config(['agent.api_token' => 'secrettoken']);

        $this->postJson('/api/v1/agent/reports', $payload, [
            'Authorization' => 'Bearer secrettoken',
        ])->assertStatus(400)
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_agent_gets_401_if_ip_not_allowed(): void
    {
        \App\Models\User::factory()->create();
        $asset = Asset::factory()->laptopMbp()->create(['asset_tag' => 'TAGIP1']);

        $payload = [
            'type' => 'test_results',
            'asset_tag' => $asset->asset_tag,
            'results' => [
                [
                    'test_slug' => $this->keyboardSlug,
                    'status' => TestResult::STATUS_PASS,
                ],
            ],
        ];

        config(['agent.api_token' => 'secrettoken']);
        config(['agent.allowed_ips' => ['10.0.0.1']]);

        $this->postJson('/api/v1/agent/reports', $payload, [
            'Authorization' => 'Bearer secrettoken',
        ])->assertStatus(401)
            ->assertJson(['message' => 'Unauthorized']);
    }
}

