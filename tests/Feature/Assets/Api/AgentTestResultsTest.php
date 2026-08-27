<?php

namespace Tests\Feature\Assets\Api;

use App\Models\Asset;
use App\Models\ComponentDefinition;
use App\Models\ModelNumberComponentTemplate;
use App\Models\TestResult;
use App\Models\TestType;
use App\Models\TestRun;
use App\Models\WorkflowProfile;
use App\Models\WorkflowProfileItem;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AgentTestResultsTest extends TestCase
{
    protected string $keyboardSlug;
    protected string $wifiSlug;

    /** @var array<string,int> */
    protected array $componentDefinitionIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $profile = WorkflowProfile::factory()->create([
            'name' => 'Agent diagnostics',
            'slug' => 'agent-diagnostics',
            'is_default' => true,
            'blocks_sale_readiness' => true,
        ]);

        foreach ([
            'keyboard' => 'Keyboard - Generic',
            'wifi' => 'Wireless - Generic',
        ] as $slug => $componentName) {
            $definition = ComponentDefinition::factory()->create(['name' => $componentName]);
            $type = TestType::factory()->create([
                'name' => $slug === 'keyboard' ? 'Keyboard' : 'Wi-Fi',
                'slug' => $slug,
                'display_order' => count($this->componentDefinitionIds),
            ]);
            $type->componentDefinitions()->sync([$definition->id]);

            WorkflowProfileItem::factory()->create([
                'workflow_profile_id' => $profile->id,
                'workflow_item_id' => $type->id,
                'sort_order' => count($this->componentDefinitionIds),
            ]);

            $this->componentDefinitionIds[$slug] = $definition->id;
        }

        $this->keyboardSlug = 'keyboard';
        $this->wifiSlug = 'wifi';
    }

    public function test_agent_can_submit_test_results(): void
    {
        \App\Models\User::factory()->create();
        $agent = \App\Models\User::factory()->create();
        $asset = Asset::factory()->laptopMbp()->create(['asset_tag' => 'TAG123']);
        $this->assignWorkflowComponentsToAsset($asset, [$this->keyboardSlug, $this->wifiSlug]);

        $payload = [
            'type' => 'test_results',
            'asset_tag' => $asset->asset_tag,
            'results' => [
                [
                    'test_slug' => $this->keyboardSlug,
                    'status' => TestResult::STATUS_PASS,
                    'note' => 'All good',
                ],
                [
                    'test_slug' => $this->wifiSlug,
                    'status' => TestResult::STATUS_NVT,
                    'note' => 'Not tested by agent',
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
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $run->readiness_context_hash);

        $this->assertDatabaseHas('workflow_results', [
            'workflow_run_id' => $run->id,
            'workflow_item_id' => $run->results()->whereHas('type', fn ($query) => $query->where('slug', $this->keyboardSlug))->value('workflow_item_id'),
            'status' => TestResult::STATUS_PASS,
            'note' => 'All good',
        ]);

        $wifiResult = $run->results()->whereHas('type', fn ($query) => $query->where('slug', $this->wifiSlug))->first();
        $this->assertNotNull($wifiResult);

        $this->assertEquals(TestResult::STATUS_NVT, $wifiResult->status);
        $this->assertEquals('Not tested by agent', $wifiResult->note);

        $this->assertDatabaseHas('workflow_results', [
            'workflow_run_id' => $run->id,
            'workflow_item_id' => $wifiResult->workflow_item_id,
            'status' => TestResult::STATUS_NVT,
        ]);

        $asset->refresh();
        $this->assertFalse((bool) $asset->tests_completed_ok);

        $this->assertDatabaseHas('workflow_audits', [
            'auditable_type' => TestRun::class,
            'auditable_id' => $run->id,
            'user_id' => $agent->id,
        ]);

        $keyboardResult = TestResult::where('workflow_run_id', $run->id)
            ->whereHas('type', fn ($query) => $query->where('slug', $this->keyboardSlug))
            ->first();
        $this->assertDatabaseHas('workflow_audits', [
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
        $this->assignWorkflowComponentsToAsset($asset, [$this->keyboardSlug]);

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

    public function test_unknown_test_slug_is_rejected_without_persisting_a_run(): void
    {
        $asset = Asset::factory()->laptopMbp()->create(['asset_tag' => 'TAGUNKNOWN']);
        $this->assignWorkflowComponentsToAsset($asset, [$this->keyboardSlug]);

        config(['agent.api_token' => 'secrettoken']);

        $this->postJson('/api/v1/agent/reports', [
            'type' => 'test_results',
            'asset_tag' => $asset->asset_tag,
            'results' => [
                [
                    'test_slug' => 'not-in-this-workflow',
                    'status' => TestResult::STATUS_PASS,
                ],
            ],
        ], [
            'Authorization' => 'Bearer secrettoken',
        ])->assertStatus(422)
            ->assertJson(['message' => 'Unknown test slugs']);

        $this->assertDatabaseCount('workflow_runs', 0);
        $this->assertDatabaseCount('workflow_results', 0);
    }

    public function test_partial_blocking_report_is_rejected_without_persisting_a_run(): void
    {
        $asset = Asset::factory()->laptopMbp()->create(['asset_tag' => 'TAGPARTIAL']);
        $this->assignWorkflowComponentsToAsset($asset, [$this->keyboardSlug, $this->wifiSlug]);

        config(['agent.api_token' => 'secrettoken']);

        $this->postJson('/api/v1/agent/reports', [
            'type' => 'test_results',
            'asset_tag' => $asset->asset_tag,
            'results' => [
                [
                    'test_slug' => $this->keyboardSlug,
                    'status' => TestResult::STATUS_PASS,
                ],
            ],
        ], [
            'Authorization' => 'Bearer secrettoken',
        ])->assertStatus(422)
            ->assertJson(['message' => 'Missing required workflow results'])
            ->assertJsonPath('errors.results.0', 'Missing required test slugs: ' . $this->wifiSlug);

        $this->assertDatabaseCount('workflow_runs', 0);
        $this->assertDatabaseCount('workflow_results', 0);
    }

    public function test_duplicate_test_slugs_are_rejected_without_persisting_a_run(): void
    {
        $asset = Asset::factory()->laptopMbp()->create(['asset_tag' => 'TAGDUPLICATE']);
        $this->assignWorkflowComponentsToAsset($asset, [$this->keyboardSlug]);

        config(['agent.api_token' => 'secrettoken']);

        $this->postJson('/api/v1/agent/reports', [
            'type' => 'test_results',
            'asset_tag' => $asset->asset_tag,
            'results' => [
                [
                    'test_slug' => $this->keyboardSlug,
                    'status' => TestResult::STATUS_PASS,
                ],
                [
                    'test_slug' => $this->keyboardSlug,
                    'status' => TestResult::STATUS_FAIL,
                ],
            ],
        ], [
            'Authorization' => 'Bearer secrettoken',
        ])->assertStatus(400)
            ->assertJsonValidationErrors(['results.0.test_slug', 'results.1.test_slug']);

        $this->assertDatabaseCount('workflow_runs', 0);
        $this->assertDatabaseCount('workflow_results', 0);
    }

    public function test_empty_results_are_rejected_without_persisting_a_run(): void
    {
        $asset = Asset::factory()->laptopMbp()->create(['asset_tag' => 'TAGEMPTY']);

        config(['agent.api_token' => 'secrettoken']);

        $this->postJson('/api/v1/agent/reports', [
            'type' => 'test_results',
            'asset_tag' => $asset->asset_tag,
            'results' => [],
        ], [
            'Authorization' => 'Bearer secrettoken',
        ])->assertStatus(400)
            ->assertJsonValidationErrors(['results']);

        $this->assertDatabaseCount('workflow_runs', 0);
        $this->assertDatabaseCount('workflow_results', 0);
    }

    public function test_result_count_is_bounded_without_persisting_a_run(): void
    {
        $asset = Asset::factory()->laptopMbp()->create(['asset_tag' => 'TAGOVERSIZED']);

        config(['agent.api_token' => 'secrettoken']);

        $results = [];
        for ($index = 0; $index < 101; $index++) {
            $results[] = [
                'test_slug' => 'result-' . $index,
                'status' => TestResult::STATUS_PASS,
            ];
        }

        $this->postJson('/api/v1/agent/reports', [
            'type' => 'test_results',
            'asset_tag' => $asset->asset_tag,
            'results' => $results,
        ], [
            'Authorization' => 'Bearer secrettoken',
        ])->assertStatus(400)
            ->assertJsonValidationErrors(['results']);

        $this->assertDatabaseCount('workflow_runs', 0);
        $this->assertDatabaseCount('workflow_results', 0);
    }

    public function test_unconfigured_or_malformed_agent_token_fails_closed_without_type_error(): void
    {
        foreach ([null, [], 123, '   '] as $configuredToken) {
            config(['agent.api_token' => $configuredToken]);

            $this->postJson('/api/v1/agent/reports', [
                'type' => 'test_results',
                'asset_tag' => 'TAGTOKEN',
                'results' => [
                    [
                        'test_slug' => $this->keyboardSlug,
                        'status' => TestResult::STATUS_PASS,
                    ],
                ],
            ], [
                'Authorization' => 'Bearer supplied-but-not-configured',
            ])->assertUnauthorized()
                ->assertJson(['message' => 'Unauthorized']);
        }

        $this->assertDatabaseCount('workflow_runs', 0);
        $this->assertDatabaseCount('workflow_results', 0);
    }

    public function test_agent_gets_401_if_ip_not_allowed(): void
    {
        \App\Models\User::factory()->create();
        $asset = Asset::factory()->laptopMbp()->create(['asset_tag' => 'TAGIP1']);
        $this->assignWorkflowComponentsToAsset($asset, [$this->keyboardSlug]);

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

    private function assignWorkflowComponentsToAsset(Asset $asset, array $slugs): void
    {
        foreach ($slugs as $index => $slug) {
            $componentDefinitionId = $this->componentDefinitionIds[$slug] ?? null;

            if (!$componentDefinitionId || !$asset->model_number_id) {
                continue;
            }

            ModelNumberComponentTemplate::updateOrCreate(
                [
                    'model_number_id' => $asset->model_number_id,
                    'component_definition_id' => $componentDefinitionId,
                    'expected_name' => $slug,
                    'slot_name' => null,
                ],
                [
                    'expected_qty' => 1,
                    'is_required' => true,
                    'sort_order' => $index,
                ]
            );
        }
    }
}

