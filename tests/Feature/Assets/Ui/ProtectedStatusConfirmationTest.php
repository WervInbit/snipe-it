<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\AssetStatusEvent;
use App\Models\Statuslabel;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\TestType;
use App\Models\User;
use App\Models\WorkflowProfile;
use App\Models\WorkflowProfileItem;
use App\Services\WorkflowRunDefinitionService;
use Tests\TestCase;

class ProtectedStatusConfirmationTest extends TestCase
{
    public function test_protected_status_with_issues_requires_current_confirmation_and_audited_reason(): void
    {
        [$asset, $target] = $this->assetAndProtectedStatus();
        $profile = $this->blockingProfile('Pre-Sale Check');
        $user = User::factory()->superuser()->create();

        $preview = $this->actingAs($user)
            ->postJson(route('hardware.status.preview', $asset), ['status_id' => $target->id])
            ->assertOk()
            ->assertJson([
                'protected' => true,
                'has_issues' => true,
                'can_override_issues' => true,
            ]);

        $hash = $preview->json('confirmation_hash');

        $this->patchJson(route('hardware.status.update', $asset), [
            'status_id' => $target->id,
            'status_confirmation_hash' => $hash,
        ])->assertJsonValidationErrors('status_override_reason');

        $this->patchJson(route('hardware.status.update', $asset), [
            'status_id' => $target->id,
            'status_confirmation_hash' => $hash,
            'status_override_reason' => 'Supervisor accepts the documented exception.',
        ])->assertOk();

        $this->assertSame($target->id, $asset->fresh()->status_id);
        $event = AssetStatusEvent::query()
            ->where('asset_id', $asset->id)
            ->where('to_status_id', $target->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($hash, $event->guard_confirmation_hash);
        $this->assertSame('Supervisor accepts the documented exception.', $event->guard_override_reason);
        $this->assertTrue($event->guard_override_details['overridden']);
        $this->assertStringContainsString($profile->name, implode(' ', $event->guard_override_details['workflow_issues']));

        $this->get(route('hardware.show', $asset))
            ->assertOk()
            ->assertSee('Readiness overridden')
            ->assertSee('Supervisor accepts the documented exception.');
    }

    public function test_user_without_override_permission_can_preview_but_cannot_confirm_issues(): void
    {
        [$asset, $target] = $this->assetAndProtectedStatus();
        $this->blockingProfile('Required Diagnostics');
        $user = User::factory()->create([
            'permissions' => json_encode([
                'assets.view' => '1',
                'assets.sale_transition' => '1',
            ]),
        ]);

        $preview = $this->actingAs($user)
            ->postJson(route('hardware.status.preview', $asset), ['status_id' => $target->id])
            ->assertOk()
            ->assertJson([
                'has_issues' => true,
                'can_override_issues' => false,
            ]);

        $this->patchJson(route('hardware.status.update', $asset), [
            'status_id' => $target->id,
            'status_confirmation_hash' => $preview->json('confirmation_hash'),
            'status_override_reason' => 'Attempted forged override.',
        ])->assertForbidden();

        $this->assertNotSame($target->id, $asset->fresh()->status_id);
    }

    public function test_clean_protected_transition_still_requires_confirmation(): void
    {
        [$asset, $target] = $this->assetAndProtectedStatus();
        [$profile, $profileItem] = $this->blockingProfileWithItem('Pre-Sale Check');
        $this->passingRun($asset, $profile, $profileItem);
        $user = User::factory()->superuser()->create();

        $this->actingAs($user)
            ->patchJson(route('hardware.status.update', $asset), ['status_id' => $target->id])
            ->assertStatus(409);

        $preview = $this->postJson(route('hardware.status.preview', $asset), [
            'status_id' => $target->id,
        ])->assertOk()->assertJson(['has_issues' => false]);

        $this->patchJson(route('hardware.status.update', $asset), [
            'status_id' => $target->id,
            'status_confirmation_hash' => $preview->json('confirmation_hash'),
        ])->assertOk();

        $event = AssetStatusEvent::query()
            ->where('asset_id', $asset->id)
            ->where('to_status_id', $target->id)
            ->latest('id')
            ->firstOrFail();
        $this->assertFalse($event->guard_override_details['overridden']);
        $this->assertNull($event->guard_override_reason);
    }

    public function test_changed_workflow_state_invalidates_an_open_confirmation(): void
    {
        [$asset, $target] = $this->assetAndProtectedStatus();
        [$profile, $profileItem] = $this->blockingProfileWithItem('Pre-Sale Check');
        $user = User::factory()->superuser()->create();

        $preview = $this->actingAs($user)
            ->postJson(route('hardware.status.preview', $asset), ['status_id' => $target->id])
            ->assertOk();

        $this->passingRun($asset, $profile, $profileItem);

        $this->patchJson(route('hardware.status.update', $asset), [
            'status_id' => $target->id,
            'status_confirmation_hash' => $preview->json('confirmation_hash'),
            'status_override_reason' => 'This snapshot is stale.',
        ])->assertStatus(409)
            ->assertJsonPath('guard.has_issues', false);
    }

    /**
     * @return array{0: Asset, 1: Statuslabel}
     */
    private function assetAndProtectedStatus(): array
    {
        $original = Statuslabel::factory()->pending()->create(['default_label' => 0]);
        $target = Statuslabel::factory()->rtd()->create([
            'default_label' => 0,
            'lifecycle_stage' => Statuslabel::LIFECYCLE_READY_FOR_SALE,
        ]);

        return [Asset::factory()->create(['status_id' => $original->id]), $target];
    }

    private function blockingProfile(string $name): WorkflowProfile
    {
        [$profile] = $this->blockingProfileWithItem($name);

        return $profile;
    }

    /**
     * @return array{0: WorkflowProfile, 1: WorkflowProfileItem}
     */
    private function blockingProfileWithItem(string $name): array
    {
        $profile = WorkflowProfile::factory()->create([
            'name' => $name,
            'blocks_sale_readiness' => true,
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

    private function passingRun(
        Asset $asset,
        WorkflowProfile $profile,
        WorkflowProfileItem $profileItem
    ): TestRun {
        $definition = app(WorkflowRunDefinitionService::class)->forProfile($asset, $profile);
        $run = TestRun::factory()->create([
            'asset_id' => $asset->id,
            'model_number_id' => $asset->model_number_id,
            'workflow_profile_id' => $profile->id,
            'readiness_context_hash' => $definition['readiness_context_hash'],
            'started_at' => now(),
            'finished_at' => now(),
        ]);
        TestResult::factory()->create([
            'workflow_run_id' => $run->id,
            'workflow_item_id' => $profileItem->workflow_item_id,
            'workflow_profile_item_id' => $profileItem->id,
            'status' => TestResult::STATUS_PASS,
            'is_required' => true,
        ]);

        return $run;
    }
}
