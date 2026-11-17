<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\ComponentDefinition;
use App\Models\ComponentInstance;
use App\Models\Statuslabel;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\TestType;
use App\Models\User;
use App\Models\WorkflowLegacyAttestation;
use App\Models\WorkflowProfile;
use App\Models\WorkflowProfileItem;
use App\Services\LegacyWorkflowAttestationService;
use App\Services\QrLabelService;
use App\Services\WorkflowItemEvidenceService;
use App\Services\WorkflowProgressionService;
use App\Services\WorkflowReadinessService;
use App\Services\WorkflowRunDefinitionService;
use Mockery;
use Tests\TestCase;

class LegacyWorkflowAttestationTest extends TestCase
{
    public function test_full_legacy_coverage_is_ready_without_fabricating_a_run_or_result(): void
    {
        $asset = Asset::factory()->create();
        [$profile, $profileItem] = $this->profileWithItems('Diagnostics', 1);
        $actor = User::factory()->create();

        app(LegacyWorkflowAttestationService::class)->createForNewAsset(
            $asset,
            [$profileItem->id],
            'Completed under the pre-Snipe-IT cabinet process.',
            null,
            $actor
        );

        $summary = app(WorkflowReadinessService::class)->summary($asset, collect([$profile]));
        $row = app(WorkflowProgressionService::class)->forProfile($asset, $profile);

        $this->assertTrue(app(WorkflowReadinessService::class)->isReady($asset, collect([$profile])));
        $this->assertSame('legacy', $summary['evidence_basis']);
        $this->assertSame(WorkflowProgressionService::STATE_COMPLETED_WITH_LEGACY_EVIDENCE, $row['state']);
        $this->assertTrue($row['can_start']);
        $this->assertDatabaseCount('workflow_runs', 0);
        $this->assertDatabaseCount('workflow_results', 0);
    }

    public function test_legacy_completion_satisfies_a_profile_dependency(): void
    {
        $asset = Asset::factory()->create();
        [$first, $firstItem] = $this->profileWithItems('Diagnostics', 1);
        [$second] = $this->profileWithItems('Cleaning', 1, 2);
        $second->prerequisites()->sync([$first->id]);

        app(LegacyWorkflowAttestationService::class)->createForNewAsset(
            $asset,
            [$firstItem->id],
            'Accepted from the historical intake sheet.',
            '2026-01-05',
            User::factory()->create()
        );

        $row = app(WorkflowProgressionService::class)->forProfile($asset, $second);

        $this->assertTrue($row['dependencies_satisfied']);
        $this->assertTrue($row['can_start']);
        $this->assertSame(
            WorkflowProgressionService::STATE_COMPLETED_WITH_LEGACY_EVIDENCE,
            $row['prerequisites']->first()['actual_state']
        );
    }

    public function test_starting_a_fully_legacy_profile_creates_real_pending_results(): void
    {
        $asset = Asset::factory()->create();
        [$profile, $profileItem] = $this->profileWithItems('Diagnostics', 1);
        $actor = User::factory()->superuser()->create();
        app(LegacyWorkflowAttestationService::class)->createForNewAsset(
            $asset,
            [$profileItem->id],
            'Accepted from the historical intake sheet.',
            null,
            $actor
        );

        $response = $this->actingAs($actor)->post(route('test-runs.store', $asset), [
            'workflow_profile_id' => $profile->id,
        ]);
        $run = TestRun::query()->where('asset_id', $asset->id)->firstOrFail();
        $result = $run->results()->firstOrFail();

        $response->assertRedirect(route('test-results.active', ['asset' => $asset->id, 'run' => $run->id]));
        $this->assertSame($profileItem->id, $result->workflow_profile_item_id);
        $this->assertSame(TestResult::STATUS_NVT, $result->status);
        $this->assertSame([], $run->legacy_attestation_snapshot);

        $this->actingAs($actor)
            ->postJson(route('test-results.partial-update', [$asset, $run, $result]), [
                'status' => TestResult::STATUS_PASS,
            ])
            ->assertOk();

        $attestation = WorkflowLegacyAttestation::query()->firstOrFail();
        $this->assertNotNull($attestation->superseded_at);
        $this->assertSame($result->id, $attestation->superseded_by_result_id);
        $this->assertNull($attestation->active_slot);
    }

    public function test_partial_legacy_coverage_creates_a_mixed_run_for_only_uncovered_items(): void
    {
        $asset = Asset::factory()->create();
        [$profile, $legacyItem, $uncoveredItem] = $this->profileWithItems('Diagnostics', 2);
        $actor = User::factory()->superuser()->create();
        app(LegacyWorkflowAttestationService::class)->createForNewAsset(
            $asset,
            [$legacyItem->id],
            'Keyboard was tested before registration.',
            null,
            $actor
        );

        $response = $this->actingAs($actor)->post(route('test-runs.store', $asset), [
            'workflow_profile_id' => $profile->id,
        ]);
        $run = TestRun::query()->where('asset_id', $asset->id)->firstOrFail();

        $response->assertRedirect(route('test-results.active', ['asset' => $asset->id, 'run' => $run->id]));
        $this->assertCount(1, $run->results);
        $this->assertSame($uncoveredItem->id, $run->results->first()->workflow_profile_item_id);
        $this->assertSame($legacyItem->id, $run->legacy_attestation_snapshot[0]['workflow_profile_item_id']);

        $result = $run->results->first();
        $this->actingAs($actor)
            ->postJson(route('test-results.partial-update', [$asset, $run, $result]), [
                'status' => TestResult::STATUS_PASS,
            ])
            ->assertOk();

        $summary = app(WorkflowReadinessService::class)->summary($asset, collect([$profile]));
        $this->assertNotNull($run->fresh()->finished_at);
        $this->assertTrue($summary['missing_profiles']->isEmpty());
        $this->assertSame('mixed', $summary['evidence_basis']);
    }

    public function test_adding_an_unrelated_item_does_not_stale_existing_evidence(): void
    {
        $asset = Asset::factory()->create();
        [$profile, $acceptedItem] = $this->profileWithItems('Diagnostics', 1);
        app(LegacyWorkflowAttestationService::class)->createForNewAsset(
            $asset,
            [$acceptedItem->id],
            'Accepted from historical records.',
            null,
            User::factory()->create()
        );
        $newType = TestType::factory()->create(['applies_to_all' => true, 'is_required' => true]);
        WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $profile->id,
            'workflow_item_id' => $newType->id,
            'is_required' => true,
            'sort_order' => 99,
        ]);

        $evidence = app(WorkflowItemEvidenceService::class)->forProfile($asset, $profile->fresh());

        $this->assertSame(
            WorkflowItemEvidenceService::STATE_LEGACY_ACCEPTED,
            $evidence['entries']->firstWhere('profile_item.id', $acceptedItem->id)['state']
        );
        $this->assertSame(1, $evidence['entries']->where('state', WorkflowItemEvidenceService::STATE_PENDING)->count());
    }

    public function test_requirement_change_marks_legacy_evidence_stale(): void
    {
        $asset = Asset::factory()->create();
        [$profile, $profileItem] = $this->profileWithItems('Diagnostics', 1);
        app(LegacyWorkflowAttestationService::class)->createForNewAsset(
            $asset,
            [$profileItem->id],
            'Accepted from historical records.',
            null,
            User::factory()->create()
        );

        $profileItem->item->update(['instructions' => 'A materially revised diagnostic procedure.']);
        $evidence = app(WorkflowItemEvidenceService::class)->forProfile($asset, $profile->fresh());

        $this->assertSame(WorkflowItemEvidenceService::STATE_LEGACY_STALE, $evidence['entries']->first()['state']);
        $this->assertFalse($evidence['ready']);
    }

    public function test_only_relevant_component_changes_stale_component_scoped_legacy_evidence(): void
    {
        $asset = Asset::factory()->create();
        [$profile, $profileItem] = $this->profileWithItems('Diagnostics', 1);
        $relevantDefinition = ComponentDefinition::factory()->create();
        $unrelatedDefinition = ComponentDefinition::factory()->create();
        $profileItem->item->update(['applies_to_all' => false]);
        $profileItem->item->componentDefinitions()->sync([$relevantDefinition->id]);
        ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $relevantDefinition->id,
        ]);

        app(LegacyWorkflowAttestationService::class)->createForNewAsset(
            $asset,
            [$profileItem->id],
            'Accepted from historical records.',
            null,
            User::factory()->create()
        );

        ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $unrelatedDefinition->id,
        ]);
        $unchanged = app(WorkflowItemEvidenceService::class)->forProfile($asset, $profile->fresh());
        $this->assertSame(WorkflowItemEvidenceService::STATE_LEGACY_ACCEPTED, $unchanged['entries']->first()['state']);

        ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $relevantDefinition->id,
        ]);
        $changed = app(WorkflowItemEvidenceService::class)->forProfile($asset, $profile->fresh());
        $this->assertSame(WorkflowItemEvidenceService::STATE_LEGACY_STALE, $changed['entries']->first()['state']);
    }

    public function test_duplicate_real_results_cannot_be_hidden_by_legacy_evidence(): void
    {
        $asset = Asset::factory()->create();
        [$profile, $profileItem] = $this->profileWithItems('Diagnostics', 1);
        app(LegacyWorkflowAttestationService::class)->createForNewAsset(
            $asset,
            [$profileItem->id],
            'Accepted from historical records.',
            null,
            User::factory()->create()
        );
        $definition = app(WorkflowRunDefinitionService::class)->forProfile($asset, $profile);
        $run = TestRun::factory()->create([
            'asset_id' => $asset->id,
            'model_number_id' => $asset->model_number_id,
            'workflow_profile_id' => $profile->id,
            'readiness_context_hash' => $definition['readiness_context_hash'],
            'started_at' => now(),
            'finished_at' => now(),
        ]);
        TestResult::factory()->count(2)->create([
            'workflow_run_id' => $run->id,
            'workflow_item_id' => $profileItem->workflow_item_id,
            'workflow_profile_item_id' => $profileItem->id,
            'status' => TestResult::STATUS_PASS,
            'is_required' => true,
        ]);

        $evidence = app(WorkflowItemEvidenceService::class)->forProfile($asset, $profile, $run->fresh());

        $this->assertSame(WorkflowItemEvidenceService::STATE_PENDING, $evidence['entries']->first()['state']);
        $this->assertSame('invalid_result', $evidence['entries']->first()['source']);
        $this->assertFalse($evidence['ready']);
    }

    public function test_real_result_supersession_is_permanent_after_result_deletion(): void
    {
        $asset = Asset::factory()->create();
        [$profile, $profileItem] = $this->profileWithItems('Diagnostics', 1);
        app(LegacyWorkflowAttestationService::class)->createForNewAsset(
            $asset,
            [$profileItem->id],
            'Accepted from historical records.',
            null,
            User::factory()->create()
        );
        $definition = app(WorkflowRunDefinitionService::class)->forProfile($asset, $profile);
        $run = TestRun::factory()->create([
            'asset_id' => $asset->id,
            'model_number_id' => $asset->model_number_id,
            'workflow_profile_id' => $profile->id,
            'readiness_context_hash' => $definition['readiness_context_hash'],
            'started_at' => now(),
        ]);
        $result = TestResult::factory()->create([
            'workflow_run_id' => $run->id,
            'workflow_item_id' => $profileItem->workflow_item_id,
            'workflow_profile_item_id' => $profileItem->id,
            'status' => TestResult::STATUS_FAIL,
            'is_required' => true,
        ]);

        app(LegacyWorkflowAttestationService::class)->supersedeForResult($asset, $result);
        $result->delete();

        $attestation = WorkflowLegacyAttestation::query()->firstOrFail();
        $this->assertNotNull($attestation->superseded_at);
        $this->assertNull($attestation->active_slot);
        $this->assertSame(0, WorkflowLegacyAttestation::query()->active()->count());
    }

    public function test_revoking_evidence_used_by_an_open_run_materializes_a_pending_result(): void
    {
        $asset = Asset::factory()->create();
        [$profile, $legacyItem, $uncoveredItem] = $this->profileWithItems('Diagnostics', 2);
        $actor = User::factory()->superuser()->create();
        app(LegacyWorkflowAttestationService::class)->createForNewAsset(
            $asset,
            [$legacyItem->id],
            'Accepted from historical records.',
            null,
            $actor
        );
        $this->actingAs($actor)->post(route('test-runs.store', $asset), [
            'workflow_profile_id' => $profile->id,
        ]);
        $run = TestRun::query()->where('asset_id', $asset->id)->firstOrFail();
        $attestation = WorkflowLegacyAttestation::query()->active()->firstOrFail();

        app(LegacyWorkflowAttestationService::class)->revoke(
            $asset,
            $attestation,
            $actor,
            'Historical evidence was attached to the wrong laptop.'
        );

        $this->assertDatabaseHas('workflow_results', [
            'workflow_run_id' => $run->id,
            'workflow_profile_item_id' => $legacyItem->id,
            'status' => TestResult::STATUS_NVT,
        ]);
        $this->assertSame(2, $run->results()->count());
        $this->assertNull($run->fresh()->finished_at);
    }

    public function test_options_endpoint_requires_both_create_and_legacy_permissions(): void
    {
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $this->profileWithItems('Diagnostics', 1);
        $ordinary = User::factory()->create(['permissions' => json_encode(['assets.create' => '1'])]);

        $this->actingAs($ordinary)->getJson(route('hardware.legacy-workflow-items', [
            'model_id' => $model->id,
            'model_number_id' => $modelNumber->id,
        ]))->assertForbidden();

        $allowed = User::factory()->create(['permissions' => json_encode([
            'assets.create' => '1',
            'tests.manage_legacy_evidence' => '1',
        ])]);
        $this->actingAs($allowed)->get(route('hardware.legacy-workflow-items', [
            'model_id' => $model->id,
            'model_number_id' => $modelNumber->id,
        ]))->assertOk()->assertSee('legacy_profile_item_ids[]', false);
    }

    public function test_latest_summary_exposes_active_and_stale_legacy_evidence(): void
    {
        $asset = Asset::factory()->create();
        [$profile, $profileItem] = $this->profileWithItems('Diagnostics', 1);
        $actor = User::factory()->create([
            'first_name' => 'Legacy',
            'last_name' => 'Reviewer',
        ]);
        app(LegacyWorkflowAttestationService::class)->createForNewAsset(
            $asset,
            [$profileItem->id],
            'Accepted from the signed cabinet worksheet.',
            '2026-01-05',
            $actor
        );
        $apiUser = User::factory()->superuser()->create();

        $this->actingAsForApi($apiUser)
            ->getJson(route('api.assets.latest-test-summary', $asset))
            ->assertOk()
            ->assertJsonPath('run_id', null)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('completed', 1)
            ->assertJsonPath('missing_count', 0)
            ->assertJsonPath('legacy_accepted_count', 1)
            ->assertJsonPath('legacy_stale_count', 0)
            ->assertJsonPath('legacy_accepted.0.profile', $profile->name)
            ->assertJsonPath('legacy_accepted.0.item', $profileItem->item->name)
            ->assertJsonPath('legacy_accepted.0.source_tested_at', '2026-01-05')
            ->assertJsonPath('legacy_accepted.0.accepted_by', 'Legacy Reviewer')
            ->assertJsonPath('evidence_basis', 'legacy')
            ->assertJsonPath('workflow_status', 'legacy');

        $profileItem->item->update(['instructions' => 'A materially revised diagnostic procedure.']);

        $this->actingAsForApi($apiUser)
            ->getJson(route('api.assets.latest-test-summary', $asset))
            ->assertOk()
            ->assertJsonPath('completed', 0)
            ->assertJsonPath('missing_count', 1)
            ->assertJsonPath('legacy_accepted_count', 0)
            ->assertJsonPath('legacy_stale_count', 1)
            ->assertJsonPath('missing.0.type', 'legacy_stale')
            ->assertJsonPath('evidence_basis', 'pending')
            ->assertJsonPath('workflow_status', 'missing');
    }

    public function test_asset_api_index_can_filter_and_count_active_legacy_evidence(): void
    {
        $legacyAsset = Asset::factory()->create(['asset_tag' => 'LEGACY-INDEX']);
        $ordinaryAsset = Asset::factory()->create(['asset_tag' => 'ORDINARY-INDEX']);
        [, $profileItem] = $this->profileWithItems('Diagnostics', 1);
        app(LegacyWorkflowAttestationService::class)->createForNewAsset(
            $legacyAsset,
            [$profileItem->id],
            'Accepted from the signed cabinet worksheet.',
            null,
            User::factory()->create()
        );
        $apiUser = User::factory()->superuser()->create();

        $this->actingAsForApi($apiUser)
            ->getJson(route('api.assets.index', ['legacy_evidence' => 'active']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.id', $legacyAsset->id)
            ->assertJsonPath('rows.0.test_workflow_status', 'legacy')
            ->assertJsonPath('rows.0.legacy_evidence_active_count', 1)
            ->assertJsonPath('rows.0.legacy_evidence_superseded_count', 0)
            ->assertJsonPath('rows.0.legacy_evidence_revoked_count', 0);

        $this->actingAsForApi($apiUser)
            ->getJson(route('api.assets.index', ['legacy_evidence' => 'none']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.id', $ordinaryAsset->id)
            ->assertJsonPath('rows.0.legacy_evidence_active_count', 0);
    }

    public function test_revoke_endpoint_requires_permission_and_matching_asset(): void
    {
        $asset = Asset::factory()->create();
        [, $profileItem] = $this->profileWithItems('Diagnostics', 1);
        $attestation = app(LegacyWorkflowAttestationService::class)->createForNewAsset(
            $asset,
            [$profileItem->id],
            'Accepted from the signed cabinet worksheet.',
            null,
            User::factory()->create()
        )->sole();
        $viewOnly = User::factory()->create(['permissions' => json_encode(['assets.view' => '1'])]);

        $this->actingAs($viewOnly)
            ->patch(route('hardware.legacy-workflow-attestations.revoke', [$asset, $attestation]), [
                'revocation_reason' => 'Should not be authorized.',
            ])
            ->assertForbidden();

        $manager = User::factory()->create(['permissions' => json_encode([
            'assets.view' => '1',
            'tests.manage_legacy_evidence' => '1',
        ])]);
        $otherAsset = Asset::factory()->create();
        $this->actingAs($manager)
            ->patch(route('hardware.legacy-workflow-attestations.revoke', [$otherAsset, $attestation]), [
                'revocation_reason' => 'The route must not cross asset boundaries.',
            ])
            ->assertNotFound();

        $this->actingAs($manager)
            ->from(route('hardware.show', $asset))
            ->patch(route('hardware.legacy-workflow-attestations.revoke', [$asset, $attestation]), [
                'revocation_reason' => 'Historical evidence was attached to the wrong laptop.',
            ])
            ->assertRedirect(route('hardware.show', $asset));

        $attestation->refresh();
        $this->assertSame($manager->id, $attestation->revoked_by);
        $this->assertSame(
            'Historical evidence was attached to the wrong laptop.',
            $attestation->revocation_reason
        );
        $this->assertNotNull($attestation->revoked_at);
        $this->assertNull($attestation->active_slot);
        $this->assertSame(0, WorkflowLegacyAttestation::query()->active()->count());
    }

    public function test_single_asset_web_creation_commits_asset_and_evidence_without_printing_or_results(): void
    {
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        [, $profileItem] = $this->profileWithItems('Diagnostics', 1);
        $actor = User::factory()->superuser()->create();
        $labels = Mockery::mock(QrLabelService::class);
        $labels->shouldReceive('generate')->once()->with(Mockery::on(
            fn ($asset): bool => $asset instanceof Asset && $asset->asset_tag === 'LEGACY-ONE'
        ));
        app()->instance(QrLabelService::class, $labels);

        $response = $this->actingAs($actor)->post(route('hardware.store'), [
            'asset_tags' => [1 => 'LEGACY-ONE'],
            'serials' => [1 => 'LEGACY-SERIAL'],
            'model_id' => $model->id,
            'model_number_id' => $modelNumber->id,
            'status_id' => Statuslabel::factory()->create()->id,
            'legacy_profile_item_ids' => [$profileItem->id],
            'legacy_source_note' => 'Accepted from the signed cabinet worksheet.',
            'legacy_source_tested_at' => '2026-01-05',
        ]);

        $asset = Asset::query()->where('asset_tag', 'LEGACY-ONE')->firstOrFail();
        $response->assertRedirect(route('hardware.created', $asset));
        $this->assertDatabaseHas('workflow_legacy_attestations', [
            'asset_id' => $asset->id,
            'workflow_profile_item_id' => $profileItem->id,
            'source_note' => 'Accepted from the signed cabinet worksheet.',
        ]);
        $this->assertDatabaseMissing('workflow_runs', ['asset_id' => $asset->id]);
        $this->assertDatabaseCount('workflow_results', 0);
    }

    public function test_stale_legacy_selection_rolls_back_the_new_asset(): void
    {
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        [$profile, $profileItem] = $this->profileWithItems('Diagnostics', 1);
        $profile->update(['is_active' => false]);
        $before = Asset::query()->count();

        $this->actingAs(User::factory()->superuser()->create())
            ->from(route('hardware.create'))
            ->post(route('hardware.store'), [
                'asset_tags' => [1 => 'LEGACY-ROLLBACK'],
                'model_id' => $model->id,
                'model_number_id' => $modelNumber->id,
                'status_id' => Statuslabel::factory()->create()->id,
                'legacy_profile_item_ids' => [$profileItem->id],
                'legacy_source_note' => 'This stale selection must not persist.',
            ])
            ->assertRedirect(route('hardware.create'))
            ->assertSessionHasErrors('legacy_profile_item_ids');

        $this->assertSame($before, Asset::query()->count());
        $this->assertDatabaseMissing('assets', ['asset_tag' => 'LEGACY-ROLLBACK']);
        $this->assertDatabaseCount('workflow_legacy_attestations', 0);
    }

    /**
     * @return array{0: WorkflowProfile, 1: WorkflowProfileItem, 2?: WorkflowProfileItem}
     */
    private function profileWithItems(string $name, int $count, int $order = 0): array
    {
        $profile = WorkflowProfile::factory()->create([
            'name' => $name,
            'blocks_sale_readiness' => true,
            'display_order' => $order,
        ]);
        $items = collect(range(1, $count))->map(function (int $index) use ($profile, $name) {
            $type = TestType::factory()->create([
                'name' => $name.' item '.$index,
                'applies_to_all' => true,
                'is_required' => true,
            ]);

            return WorkflowProfileItem::factory()->create([
                'workflow_profile_id' => $profile->id,
                'workflow_item_id' => $type->id,
                'is_required' => true,
                'sort_order' => $index - 1,
            ]);
        });

        return [$profile, ...$items->all()];
    }
}
