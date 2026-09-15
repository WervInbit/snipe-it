<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\TestType;
use App\Models\User;
use App\Models\WorkflowProfile;
use App\Models\WorkflowProfileItem;
use App\Services\WorkflowProgressionService;
use App\Services\WorkflowReadinessService;
use App\Services\WorkflowRunDefinitionService;
use Tests\TestCase;

class WorkflowProgressionTest extends TestCase
{
    public function test_dependency_blocks_start_until_previous_required_items_pass_or_are_done(): void
    {
        $asset = Asset::factory()->create();
        [$first, $firstItem] = $this->createProfileWithItem('Diagnostics', 1);
        [$second] = $this->createProfileWithItem('Cleaning', 2);
        $second->prerequisites()->sync([$first->id]);
        $user = $this->workflowUser();

        $this->actingAs($user)
            ->post(route('test-runs.store', $asset), [
                'workflow_profile_id' => $second->id,
                'confirm_workflow_override' => '1',
                'workflow_override_reason' => 'Forged browser fields.',
            ])
            ->assertSessionHasErrors('workflow_profile_id');

        $this->assertDatabaseMissing('workflow_runs', [
            'asset_id' => $asset->id,
            'workflow_profile_id' => $second->id,
        ]);

        $firstRun = $this->createRun($asset, $first, $firstItem, TestResult::STATUS_PASS);

        $this->actingAs($user)
            ->post(route('test-runs.store', $asset), ['workflow_profile_id' => $second->id])
            ->assertRedirect();

        $secondRun = TestRun::query()
            ->where('asset_id', $asset->id)
            ->where('workflow_profile_id', $second->id)
            ->firstOrFail();

        $this->assertSame($firstRun->id, $secondRun->prerequisite_snapshot[0]['satisfying_run_id']);
    }

    public function test_successful_dependency_is_not_satisfied_by_completion_with_issues(): void
    {
        $asset = Asset::factory()->create();
        [$first, $firstItem] = $this->createProfileWithItem('Diagnostics', 1);
        [$second] = $this->createProfileWithItem('Pre-sale', 2);
        $second->prerequisites()->sync([$first->id]);
        $this->createRun($asset, $first, $firstItem, TestResult::STATUS_FAIL);

        $row = app(WorkflowProgressionService::class)->forProfile($asset, $second);

        $this->assertTrue($row['dependency_override_required']);
        $this->assertSame(
            WorkflowProgressionService::STATE_COMPLETED_WITH_ISSUES,
            $row['blockers']->first()['actual_state']
        );
    }

    public function test_partial_required_result_set_never_satisfies_a_dependency(): void
    {
        $asset = Asset::factory()->create();
        [$first, $firstItem] = $this->createProfileWithItem('Diagnostics', 1);
        $secondRequiredType = TestType::factory()->create([
            'name' => 'Diagnostics second required item',
            'applies_to_all' => true,
            'is_required' => true,
        ]);
        WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $first->id,
            'workflow_item_id' => $secondRequiredType->id,
            'is_required' => true,
            'sort_order' => 1,
        ]);
        [$second] = $this->createProfileWithItem('Cleaning', 2);
        $second->prerequisites()->sync([$first->id]);

        $this->createRun($asset, $first, $firstItem, TestResult::STATUS_PASS);

        $row = app(WorkflowProgressionService::class)->forProfile($asset, $second);

        $this->assertTrue($row['dependency_override_required']);
        $this->assertSame(
            WorkflowProgressionService::STATE_IN_PROGRESS,
            $row['blockers']->first()['actual_state']
        );
    }

    public function test_only_required_items_gate_the_next_workflow(): void
    {
        $asset = Asset::factory()->create();
        [$first, $requiredItem] = $this->createProfileWithItem('Laptop wipen', 1);
        [$second] = $this->createProfileWithItem('Windows installeren en updaten', 2);
        $optionalType = TestType::factory()->create([
            'name' => 'Optional note',
            'applies_to_all' => true,
            'is_required' => false,
        ]);
        $optionalItem = WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $first->id,
            'workflow_item_id' => $optionalType->id,
            'is_required' => false,
            'sort_order' => 1,
        ]);
        $second->prerequisites()->sync([$first->id]);

        $run = $this->createRun($asset, $first, $requiredItem, TestResult::STATUS_PASS);
        TestResult::factory()->create([
            'workflow_run_id' => $run->id,
            'workflow_item_id' => $optionalItem->workflow_item_id,
            'workflow_profile_item_id' => $optionalItem->id,
            'status' => TestResult::STATUS_NVT,
            'is_required' => false,
            'sort_order' => 1,
        ]);
        $run->syncFinishedAtFromResults();

        $firstRow = app(WorkflowProgressionService::class)->forProfile($asset, $first);
        $secondRow = app(WorkflowProgressionService::class)->forProfile($asset, $second);

        $this->assertSame(WorkflowProgressionService::STATE_COMPLETED_SUCCESSFULLY, $firstRow['state']);
        $this->assertNotNull($run->fresh()->finished_at);
        $this->assertTrue($secondRow['can_start']);
    }

    public function test_required_failure_blocks_a_successful_dependency_even_when_optional_items_pass(): void
    {
        $asset = Asset::factory()->create();
        [$first, $requiredItem] = $this->createProfileWithItem('Standard Diagnostics', 1);
        [$second] = $this->createProfileWithItem('Cleaning', 2);
        $optionalType = TestType::factory()->create([
            'name' => 'Optional check',
            'applies_to_all' => true,
            'is_required' => false,
        ]);
        $optionalItem = WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $first->id,
            'workflow_item_id' => $optionalType->id,
            'is_required' => false,
            'sort_order' => 1,
        ]);
        $second->prerequisites()->sync([$first->id]);

        $run = $this->createRun($asset, $first, $requiredItem, TestResult::STATUS_FAIL);
        TestResult::factory()->create([
            'workflow_run_id' => $run->id,
            'workflow_item_id' => $optionalItem->workflow_item_id,
            'workflow_profile_item_id' => $optionalItem->id,
            'status' => TestResult::STATUS_PASS,
            'is_required' => false,
            'sort_order' => 1,
        ]);

        $row = app(WorkflowProgressionService::class)->forProfile($asset, $second);

        $this->assertTrue($row['dependency_override_required']);
        $this->assertSame(
            WorkflowProgressionService::STATE_COMPLETED_WITH_ISSUES,
            $row['blockers']->first()['actual_state']
        );
    }

    public function test_an_override_does_not_waive_earlier_dependencies_for_later_workflows(): void
    {
        $asset = Asset::factory()->create();
        [$first, $firstItem] = $this->createProfileWithItem('Laptop wipen', 1);
        [$second, $secondItem] = $this->createProfileWithItem('Windows installeren', 2);
        [$third] = $this->createProfileWithItem('Standard diagnostics', 3);
        $second->prerequisites()->sync([$first->id]);
        $third->prerequisites()->sync([$second->id]);

        $this->createRun($asset, $second, $secondItem, TestResult::STATUS_PASS);

        $blockedRow = app(WorkflowProgressionService::class)->forProfile($asset, $third);

        $this->assertTrue($blockedRow['dependency_override_required']);
        $this->assertSame($second->id, $blockedRow['blockers']->first()['profile_id']);
        $this->assertSame(
            WorkflowProgressionService::STATE_COMPLETED_SUCCESSFULLY,
            $blockedRow['blockers']->first()['actual_state']
        );
        $this->assertFalse($blockedRow['blockers']->first()['prerequisites_satisfied']);

        $this->createRun($asset, $first, $firstItem, TestResult::STATUS_PASS);

        $this->assertTrue(
            app(WorkflowProgressionService::class)->forProfile($asset, $third)['can_start']
        );
    }

    public function test_workflow_without_applicable_items_is_visible_but_cannot_start_or_satisfy_readiness(): void
    {
        $asset = Asset::factory()->create();
        $profile = WorkflowProfile::factory()->create([
            'name' => 'Supervisor release',
            'blocks_sale_readiness' => true,
            'display_order' => 1,
        ]);
        $scopedItem = TestType::factory()->create([
            'name' => 'Non-applicable item',
            'applies_to_all' => false,
        ]);
        WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $profile->id,
            'workflow_item_id' => $scopedItem->id,
            'is_required' => true,
        ]);
        $user = $this->workflowUser();

        $row = app(WorkflowProgressionService::class)->forProfile($asset, $profile);

        $this->assertNotNull($row);
        $this->assertTrue($row['configuration_blocked']);
        $this->assertFalse($row['can_start']);
        $this->assertFalse($asset->liveTestsCompletedOk());

        $this->actingAs($user)
            ->post(route('test-runs.store', $asset), [
                'workflow_profile_id' => $profile->id,
                'extra_workflow_item_ids' => [TestType::factory()->create()->id],
            ])
            ->assertSessionHasErrors('workflow_profile_id');

        $this->assertDatabaseMissing('workflow_runs', [
            'asset_id' => $asset->id,
            'workflow_profile_id' => $profile->id,
        ]);
    }

    public function test_permitted_override_requires_confirmation_and_reason_and_is_audited(): void
    {
        $asset = Asset::factory()->create();
        [$first] = $this->createProfileWithItem('Diagnostics', 1);
        [$second] = $this->createProfileWithItem('Cleaning', 2);
        $second->prerequisites()->sync([$first->id]);
        $user = $this->workflowUser(['tests.override_dependencies' => '1']);

        $this->actingAs($user)
            ->post(route('test-runs.store', $asset), [
                'workflow_profile_id' => $second->id,
                'confirm_workflow_override' => '1',
            ])
            ->assertSessionHasErrors('workflow_override_reason');

        $this->actingAs($user)
            ->post(route('test-runs.store', $asset), [
                'workflow_profile_id' => $second->id,
                'confirm_workflow_override' => '1',
                'workflow_override_reason' => 'Manager approved parallel cleaning.',
            ])
            ->assertRedirect();

        $run = TestRun::query()
            ->where('asset_id', $asset->id)
            ->where('workflow_profile_id', $second->id)
            ->firstOrFail();

        $this->assertSame($user->id, $run->guard_override_by);
        $this->assertSame('Manager approved parallel cleaning.', $run->guard_override_reason);
        $this->assertSame(['dependencies'], $run->guard_override_details['types']);
        $this->assertNotNull($run->guard_override_at);
    }

    public function test_starting_an_in_progress_workflow_continues_the_existing_run(): void
    {
        $asset = Asset::factory()->create();
        [$profile] = $this->createProfileWithItem('Diagnostics', 1);
        $user = $this->workflowUser();

        $this->actingAs($user)->post(route('test-runs.store', $asset), [
            'workflow_profile_id' => $profile->id,
        ]);
        $run = TestRun::query()->where('asset_id', $asset->id)->firstOrFail();

        $this->actingAs($user)
            ->post(route('test-runs.store', $asset), ['workflow_profile_id' => $profile->id])
            ->assertRedirect(route('test-results.active', ['asset' => $asset->id, 'run' => $run->id]));

        $this->assertSame(1, TestRun::query()->where('asset_id', $asset->id)->count());
    }

    public function test_completed_workflow_remains_editable_and_starting_another_run_requires_confirmation(): void
    {
        $asset = Asset::factory()->create();
        [$profile, $profileItem] = $this->createProfileWithItem('Diagnostics', 1);
        $this->createRun($asset, $profile, $profileItem, TestResult::STATUS_PASS);
        $user = $this->workflowUser(['tests.start_new_run' => '1']);

        $this->actingAs($user)
            ->post(route('test-runs.store', $asset), ['workflow_profile_id' => $profile->id])
            ->assertSessionHasErrors('confirm_workflow_override');

        $this->actingAs($user)
            ->post(route('test-runs.store', $asset), [
                'workflow_profile_id' => $profile->id,
                'confirm_workflow_override' => '1',
                'workflow_override_reason' => 'A second verification run is required.',
            ])
            ->assertRedirect();

        $this->assertSame(2, TestRun::query()->where('asset_id', $asset->id)->count());
    }

    public function test_operator_can_edit_a_completed_run_but_cannot_start_another(): void
    {
        $asset = Asset::factory()->create();
        [$profile, $profileItem] = $this->createProfileWithItem('Diagnostics', 1);
        $run = $this->createRun($asset, $profile, $profileItem, TestResult::STATUS_PASS);
        $user = $this->workflowUser();

        $this->assertTrue($user->can('update', $run));

        $this->actingAs($user)
            ->post(route('test-runs.store', $asset), [
                'workflow_profile_id' => $profile->id,
                'confirm_workflow_override' => '1',
                'workflow_override_reason' => 'Forged start-new fields.',
            ])
            ->assertSessionHasErrors('workflow_profile_id');

        $this->assertSame(1, TestRun::query()->where('asset_id', $asset->id)->count());
    }

    public function test_applicable_prerequisites_are_numbered_before_dependents(): void
    {
        $asset = Asset::factory()->create();
        [$prerequisite] = $this->createProfileWithItem('Diagnostics', 50);
        [$dependent] = $this->createProfileWithItem('Cleaning', 10);
        $dependent->prerequisites()->sync([$prerequisite->id]);

        $rows = app(WorkflowProgressionService::class)->forAsset($asset);

        $this->assertSame(
            [$prerequisite->id, $dependent->id],
            $rows->pluck('profile.id')->map(fn ($id): int => (int) $id)->all()
        );
        $this->assertSame([1, 2], $rows->pluck('position')->all());
    }

    public function test_default_profile_selection_is_independent_from_display_order(): void
    {
        $asset = Asset::factory()->create();
        [$first] = $this->createProfileWithItem('Laptop wipen', 0);
        [$default] = $this->createProfileWithItem('Standard Diagnostics', 50);
        $default->update(['is_default' => true]);

        $this->assertSame($default->id, WorkflowProfile::defaultForAsset($asset)?->id);
        $orderedIds = WorkflowProfile::query()->ordered()->pluck('id')->map(fn ($id): int => (int) $id);
        $this->assertTrue($orderedIds->search($first->id) < $orderedIds->search($default->id));
    }

    public function test_reordering_profiles_does_not_make_completed_runs_stale(): void
    {
        $asset = Asset::factory()->create();
        [$profile, $profileItem] = $this->createProfileWithItem('Cleaning', 5);
        $definition = app(WorkflowRunDefinitionService::class)->forProfile($asset, $profile);
        $run = TestRun::factory()->create([
            'asset_id' => $asset->id,
            'model_number_id' => $asset->model_number_id,
            'workflow_profile_id' => $profile->id,
            'profile_display_order_snapshot' => 5,
            'readiness_context_hash' => $definition['legacy_readiness_context_hash'],
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
        ]);
        TestResult::factory()->create([
            'workflow_run_id' => $run->id,
            'workflow_item_id' => $profileItem->workflow_item_id,
            'workflow_profile_item_id' => $profileItem->id,
            'status' => TestResult::STATUS_PASS,
            'is_required' => true,
        ]);

        $profile->update(['display_order' => 100]);

        $row = app(WorkflowProgressionService::class)->forProfile($asset, $profile);

        $this->assertSame($run->id, $row['run']->id);
        $this->assertSame(WorkflowProgressionService::STATE_COMPLETED_SUCCESSFULLY, $row['state']);
        $this->assertTrue(app(WorkflowReadinessService::class)->isReady($asset, collect([$profile])));
    }

    public function test_completion_timestamp_is_stable_while_a_completed_run_is_edited(): void
    {
        $asset = Asset::factory()->create();
        [$profile, $profileItem] = $this->createProfileWithItem('Diagnostics', 1);
        $run = $this->createRun($asset, $profile, $profileItem, TestResult::STATUS_PASS);
        $run->syncFinishedAtFromResults();
        $finishedAt = $run->fresh()->finished_at;

        $run->results()->firstOrFail()->update(['note' => 'Evidence clarified later.']);
        $run->fresh()->syncFinishedAtFromResults();

        $this->assertTrue($finishedAt->equalTo($run->fresh()->finished_at));
    }

    public function test_editing_an_older_run_never_promotes_it_to_current(): void
    {
        $asset = Asset::factory()->create();
        [$profile, $profileItem] = $this->createProfileWithItem('Diagnostics', 1);
        $older = $this->createRun($asset, $profile, $profileItem, TestResult::STATUS_PASS);
        $older->update(['started_at' => now()->subHours(2), 'finished_at' => now()]);
        $newer = $this->createRun($asset, $profile, $profileItem, TestResult::STATUS_FAIL);
        $newer->update(['started_at' => now()->subHour(), 'finished_at' => now()->subHour()]);

        $older->results()->firstOrFail()->update(['note' => 'Late correction.']);
        $older->fresh()->syncFinishedAtFromResults();

        $row = app(WorkflowProgressionService::class)->forProfile($asset, $profile);
        $this->assertSame($newer->id, $row['run']->id);
        $this->assertSame(WorkflowProgressionService::STATE_COMPLETED_WITH_ISSUES, $row['state']);
    }

    public function test_execution_level_guards_starting_and_editing_runs(): void
    {
        $asset = Asset::factory()->create();
        [$profile, $profileItem] = $this->createProfileWithItem('Supervisor review', 1);
        $profile->update(['execution_level' => WorkflowProfile::EXECUTION_SUPERVISOR]);
        $operator = $this->workflowUser();

        $this->actingAs($operator)
            ->post(route('test-runs.store', $asset), ['workflow_profile_id' => $profile->id])
            ->assertForbidden();

        $run = $this->createRun($asset, $profile, $profileItem, TestResult::STATUS_PASS);
        $this->assertFalse($operator->can('update', $run));

        $supervisor = $this->workflowUser(['tests.execute.supervisor' => '1']);
        $this->assertTrue($supervisor->can('update', $run));
    }

    public function test_legacy_inactive_prerequisite_is_a_configuration_blocker_for_sale_readiness(): void
    {
        $asset = Asset::factory()->create();
        [$prerequisite] = $this->createProfileWithItem('Diagnostics', 0);
        [$saleProfile, $saleItem] = $this->createProfileWithItem('Pre-Sale Check', 1);
        $saleProfile->update(['blocks_sale_readiness' => true]);
        $saleProfile->prerequisites()->sync([$prerequisite->id]);
        $this->createRun($asset, $saleProfile, $saleItem, TestResult::STATUS_PASS);

        $prerequisite->update(['is_active' => false]);
        $row = app(WorkflowProgressionService::class)->forProfile($asset, $saleProfile);
        $summary = $asset->latestTestIssueSummary();

        $this->assertTrue($row['dependency_override_required']);
        $this->assertSame('inactive', $row['blockers']->first()['actual_state']);
        $this->assertTrue($summary['missing_run']);
        $this->assertTrue($summary['missing_profiles']->contains('Diagnostics'));
    }

    /**
     * @return array{0: WorkflowProfile, 1: WorkflowProfileItem}
     */
    private function createProfileWithItem(string $name, int $order): array
    {
        $profile = WorkflowProfile::factory()->create([
            'name' => $name,
            'display_order' => $order,
            'repeat_policy' => WorkflowProfile::REPEAT_OVERRIDE_REQUIRED,
        ]);
        $type = TestType::factory()->create([
            'name' => $name . ' item',
            'applies_to_all' => true,
            'is_required' => true,
        ]);
        $profileItem = WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $profile->id,
            'workflow_item_id' => $type->id,
            'is_required' => true,
        ]);

        return [$profile, $profileItem];
    }

    private function createRun(
        Asset $asset,
        WorkflowProfile $profile,
        WorkflowProfileItem $profileItem,
        string $status
    ): TestRun {
        $hash = app(WorkflowRunDefinitionService::class)
            ->forProfile($asset, $profile)['readiness_context_hash'];
        $run = TestRun::factory()->create([
            'asset_id' => $asset->id,
            'model_number_id' => $asset->model_number_id,
            'workflow_profile_id' => $profile->id,
            'readiness_context_hash' => $hash,
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
        ]);
        TestResult::factory()->create([
            'workflow_run_id' => $run->id,
            'workflow_item_id' => $profileItem->workflow_item_id,
            'workflow_profile_item_id' => $profileItem->id,
            'status' => $status,
            'is_required' => true,
        ]);

        return $run;
    }

    private function workflowUser(array $extraPermissions = []): User
    {
        return User::factory()->create([
            'permissions' => json_encode(array_merge([
                'assets.view' => '1',
                'tests.execute' => '1',
                'tests.edit_runs' => '1',
            ], $extraPermissions)),
        ]);
    }
}
