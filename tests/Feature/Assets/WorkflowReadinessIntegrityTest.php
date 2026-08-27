<?php

namespace Tests\Feature\Assets;

use App\Http\Transformers\AssetsTransformer;
use App\Models\Asset;
use App\Models\ComponentDefinition;
use App\Models\ComponentInstance;
use App\Models\ModelNumber;
use App\Models\Statuslabel;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\TestType;
use App\Models\User;
use App\Models\WorkflowProfile;
use App\Models\WorkflowProfileItem;
use App\Services\WorkflowRunDefinitionService;
use App\Services\WorkflowReadinessService;
use Illuminate\Support\Str;
use Tests\TestCase;

class WorkflowReadinessIntegrityTest extends TestCase
{
    public function test_finished_current_run_with_exactly_one_pass_per_required_item_is_ready(): void
    {
        [$asset, $profile, $profileItems, $hash] = $this->workflow(2);
        $run = $this->createWorkflowRun($asset, $profile, $hash);
        $this->createWorkflowResult($run, $profileItems[0], TestResult::STATUS_PASS);
        $this->createWorkflowResult($run, $profileItems[1], TestResult::STATUS_PASS);

        $asset->refreshTestCompletionFlag();

        $this->assertTrue((bool) $asset->fresh()->tests_completed_ok);
    }

    public function test_empty_partial_duplicate_and_nvt_runs_fail_closed(): void
    {
        foreach (['empty', 'partial', 'duplicate', 'nvt', 'wrong_profile_item'] as $scenario) {
            [$asset, $profile, $profileItems, $hash] = $this->workflow(2);
            $run = $this->createWorkflowRun($asset, $profile, $hash);

            if ($scenario !== 'empty') {
                $this->createWorkflowResult(
                    $run,
                    $profileItems[0],
                    $scenario === 'nvt' ? TestResult::STATUS_NVT : TestResult::STATUS_PASS
                );
            }
            if ($scenario === 'duplicate') {
                $this->createWorkflowResult($run, $profileItems[0], TestResult::STATUS_PASS);
                $this->createWorkflowResult($run, $profileItems[1], TestResult::STATUS_PASS);
            } elseif ($scenario === 'wrong_profile_item') {
                $run->results()->delete();
                $wrongResult = $this->createWorkflowResult($run, $profileItems[0], TestResult::STATUS_PASS);
                $wrongResult->forceFill(['workflow_profile_item_id' => null])->saveQuietly();
                $this->createWorkflowResult($run, $profileItems[1], TestResult::STATUS_PASS);
            }

            $asset->refreshTestCompletionFlag();

            $this->assertFalse((bool) $asset->fresh()->tests_completed_ok, $scenario);
        }
    }

    public function test_newer_unfinished_run_masks_an_older_passing_run(): void
    {
        [$asset, $profile, $profileItems, $hash] = $this->workflow();
        $passingRun = $this->createWorkflowRun($asset, $profile, $hash, now()->subHour());
        $this->createWorkflowResult($passingRun, $profileItems[0], TestResult::STATUS_PASS);

        $unfinishedRun = $this->createWorkflowRun($asset, $profile, $hash, null);
        $this->createWorkflowResult($unfinishedRun, $profileItems[0], TestResult::STATUS_PASS);

        $asset->refreshTestCompletionFlag();

        $this->assertFalse((bool) $asset->fresh()->tests_completed_ok);
        $this->assertSame($unfinishedRun->id, $asset->latestTestIssueSummary()['run']->id);
    }

    public function test_model_change_profile_edit_and_legacy_null_hash_invalidate_readiness(): void
    {
        foreach (['model', 'profile', 'null_hash'] as $scenario) {
            [$asset, $profile, $profileItems, $hash] = $this->workflow();
            $run = $this->createWorkflowRun($asset, $profile, $scenario === 'null_hash' ? null : $hash);
            $this->createWorkflowResult($run, $profileItems[0], TestResult::STATUS_PASS);

            if ($scenario === 'model') {
                $asset->model_number_id = ModelNumber::factory()->create()->id;
                $asset->save();
            } elseif ($scenario === 'profile') {
                $profileItems[0]->sort_order = 50;
                $profileItems[0]->save();
            }

            $asset->refreshTestCompletionFlag();

            $this->assertFalse((bool) $asset->fresh()->tests_completed_ok, $scenario);
        }
    }

    public function test_unsaved_model_number_candidate_is_used_for_live_readiness(): void
    {
        [$asset, $profile, $profileItems, $hash] = $this->workflow();
        $run = $this->createWorkflowRun($asset, $profile, $hash);
        $this->createWorkflowResult($run, $profileItems[0], TestResult::STATUS_PASS);
        $asset->model_number_id = ModelNumber::factory()->create()->id;

        $profiles = WorkflowProfile::query()->whereKey($profile->id)->get();

        $this->assertFalse(app(WorkflowReadinessService::class)->isReady($asset, $profiles));
    }

    public function test_component_identity_condition_and_lifecycle_changes_invalidate_readiness(): void
    {
        foreach (['condition', 'lifecycle', 'same_definition_swap'] as $scenario) {
            [$asset, $profile, $profileItems] = $this->workflow();
            $definition = ComponentDefinition::factory()->create();
            $component = ComponentInstance::factory()
                ->installed($asset->id)
                ->create(['component_definition_id' => $definition->id]);
            $hash = app(WorkflowRunDefinitionService::class)
                ->forProfile($asset, $profile)['readiness_context_hash'];
            $run = $this->createWorkflowRun($asset, $profile, $hash);
            $this->createWorkflowResult($run, $profileItems[0], TestResult::STATUS_PASS);

            if ($scenario === 'condition') {
                $component->update([
                    'condition_status' => ComponentInstance::CONDITION_STATUS_DAMAGED,
                    'condition_code' => ComponentInstance::CONDITION_BROKEN,
                ]);
            } else {
                $component->update([
                    'status' => ComponentInstance::STATUS_IN_STOCK,
                    'lifecycle_status' => ComponentInstance::LIFECYCLE_IN_STOCK,
                    'current_asset_id' => null,
                    'root_asset_id' => null,
                ]);

                if ($scenario === 'same_definition_swap') {
                    ComponentInstance::factory()
                        ->installed($asset->id)
                        ->create(['component_definition_id' => $definition->id]);
                }
            }

            $asset->refreshTestCompletionFlag();

            $this->assertFalse((bool) $asset->fresh()->tests_completed_ok, $scenario);
        }
    }

    public function test_item_execution_and_applicability_edits_invalidate_readiness_even_when_item_stays_applicable(): void
    {
        foreach (['instructions', 'component_pivot'] as $scenario) {
            [$asset, $profile, $profileItems, $hash] = $this->workflow();
            $run = $this->createWorkflowRun($asset, $profile, $hash);
            $this->createWorkflowResult($run, $profileItems[0], TestResult::STATUS_PASS);
            $item = $profileItems[0]->item()->firstOrFail();

            if ($scenario === 'instructions') {
                $item->update(['instructions' => 'Use the revised V1 procedure.']);
            } else {
                $item->componentDefinitions()->attach(ComponentDefinition::factory()->create()->id);
            }

            $asset->refreshTestCompletionFlag();

            $this->assertFalse((bool) $asset->fresh()->tests_completed_ok, $scenario);
        }
    }

    public function test_sale_transition_uses_live_readiness_when_cached_flag_is_true(): void
    {
        [$asset, $profile, $profileItems] = $this->workflow();
        $run = $this->createWorkflowRun($asset, $profile, null);
        $this->createWorkflowResult($run, $profileItems[0], TestResult::STATUS_PASS);
        $asset->forceFill(['tests_completed_ok' => true])->saveQuietly();

        $ready = Statuslabel::factory()->rtd()->create([
            'name' => 'Ready for Sale ' . Str::uuid(),
            'lifecycle_stage' => Statuslabel::LIFECYCLE_READY_FOR_SALE,
            'default_label' => 0,
        ]);
        $user = User::factory()->superuser()->create();
        $originalStatusId = $asset->status_id;

        $this->actingAs($user)
            ->from(route('hardware.show', $asset))
            ->patch(route('hardware.status.update', $asset), [
                'status_id' => $ready->id,
            ])
            ->assertRedirect(route('hardware.show', $asset))
            ->assertSessionHas('requires_ack_failed_tests');

        $this->assertSame($originalStatusId, $asset->fresh()->status_id);
    }

    public function test_full_edit_checks_the_candidate_model_number_before_persisting_it(): void
    {
        [$asset, $profile, $profileItems] = $this->workflow();
        $currentModelNumber = ModelNumber::factory()->create(['model_id' => $asset->model_id]);
        $candidateModelNumber = ModelNumber::factory()->create(['model_id' => $asset->model_id]);
        $asset->model_number_id = $currentModelNumber->id;
        $asset->save();
        $hash = app(WorkflowRunDefinitionService::class)
            ->forProfile($asset, $profile)['readiness_context_hash'];
        $run = $this->createWorkflowRun($asset, $profile, $hash);
        $this->createWorkflowResult($run, $profileItems[0], TestResult::STATUS_PASS);
        $asset->refreshTestCompletionFlag();
        $this->assertTrue((bool) $asset->fresh()->tests_completed_ok);

        $ready = Statuslabel::factory()->rtd()->create([
            'name' => 'Ready for Sale ' . Str::uuid(),
            'lifecycle_stage' => Statuslabel::LIFECYCLE_READY_FOR_SALE,
            'default_label' => 0,
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->from(route('hardware.edit', $asset))
            ->put(route('hardware.update', $asset), [
                'asset_tags' => $asset->asset_tag,
                'model_id' => $asset->model_id,
                'model_number_id' => $candidateModelNumber->id,
                'status_id' => $ready->id,
            ])
            ->assertRedirect(route('hardware.edit', $asset))
            ->assertSessionHas('requires_ack_failed_tests');

        $this->assertSame($currentModelNumber->id, $asset->fresh()->model_number_id);
    }

    public function test_api_model_number_change_invalidates_cached_readiness(): void
    {
        $asset = Asset::factory()->create();
        $newModelNumber = ModelNumber::factory()->create();
        $asset->forceFill(['tests_completed_ok' => true])->saveQuietly();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->patchJson(route('api.assets.update', $asset), [
                'model_id' => $newModelNumber->model_id,
                'model_number_id' => $newModelNumber->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('success');

        $this->assertFalse((bool) $asset->fresh()->tests_completed_ok);
    }

    public function test_asset_transformer_does_not_render_stale_cached_readiness_as_green(): void
    {
        [$asset, $profile, $profileItems] = $this->workflow();
        $run = $this->createWorkflowRun($asset, $profile, null);
        $this->createWorkflowResult($run, $profileItems[0], TestResult::STATUS_PASS);
        $asset->forceFill([
            'tests_completed_ok' => true,
            'test_runs_count' => 1,
        ]);

        $payload = app(AssetsTransformer::class)->transformAsset($asset);

        $this->assertFalse($payload['tests_completed_ok']);
        $this->assertSame('attention', $payload['test_workflow_status']);
    }

    /**
     * @return array{Asset, WorkflowProfile, array<int, WorkflowProfileItem>, string}
     */
    private function workflow(int $itemCount = 1): array
    {
        $asset = Asset::factory()->create();
        $profile = WorkflowProfile::factory()->create([
            'blocks_sale_readiness' => true,
            'is_active' => true,
        ]);
        $profileItems = [];

        for ($index = 0; $index < $itemCount; $index++) {
            $item = TestType::factory()->create([
                'applies_to_all' => true,
                'is_required' => true,
                'display_order' => $index,
            ]);
            $profileItems[] = WorkflowProfileItem::factory()->create([
                'workflow_profile_id' => $profile->id,
                'workflow_item_id' => $item->id,
                'sort_order' => $index,
                // Deliberately disagree: TestType is authoritative.
                'is_required' => false,
            ]);
        }

        $hash = app(WorkflowRunDefinitionService::class)
            ->forProfile($asset, $profile)['readiness_context_hash'];

        return [$asset, $profile, $profileItems, $hash];
    }

    private function createWorkflowRun(
        Asset $asset,
        WorkflowProfile $profile,
        ?string $hash,
        mixed $finishedAt = false
    ): TestRun {
        return TestRun::factory()->create([
            'asset_id' => $asset->id,
            'model_number_id' => $asset->model_number_id,
            'workflow_profile_id' => $profile->id,
            'readiness_context_hash' => $hash,
            'started_at' => now()->subMinute(),
            'finished_at' => $finishedAt === false ? now() : $finishedAt,
        ]);
    }

    private function createWorkflowResult(TestRun $run, WorkflowProfileItem $profileItem, string $status): TestResult
    {
        return TestResult::factory()->create([
            'workflow_run_id' => $run->id,
            'workflow_item_id' => $profileItem->workflow_item_id,
            'workflow_profile_item_id' => $profileItem->id,
            'status' => $status,
            // Deliberately disagree: current TestType is authoritative.
            'is_required' => false,
        ]);
    }
}
