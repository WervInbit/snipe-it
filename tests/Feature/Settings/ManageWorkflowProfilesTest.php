<?php

namespace Tests\Feature\Settings;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\TestType;
use App\Models\User;
use App\Models\WorkflowProfile;
use App\Models\WorkflowProfileItem;
use Tests\TestCase;

class ManageWorkflowProfilesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_admin_can_navigate_from_profile_index_to_item_subpage(): void
    {
        $profile = WorkflowProfile::factory()->create([
            'name' => 'Standard Diagnostics',
        ]);

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('settings.workflow-profiles.index'));

        $response->assertOk();
        $response->assertSee('Standard Diagnostics');
        $response->assertSee(route('settings.workflow-profiles.items.edit', $profile), false);
        $response->assertDontSee('Items for Standard Diagnostics');
    }

    public function test_dependency_select_uses_stable_modal_positioning_without_scroll_lock(): void
    {
        WorkflowProfile::factory()->count(2)->create();

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('settings.workflow-profiles.index'));

        $response->assertOk();
        $response->assertSee("dropdownParent: dropdownParent", false);
        $response->assertSee("select.closest('.modal-content')", false);
        $response->assertSee("modal.off('scroll.select2')", false);
    }

    public function test_profile_index_uses_configured_order_even_when_default_is_later(): void
    {
        $diagnostics = WorkflowProfile::factory()->create([
            'name' => 'Standard Diagnostics',
            'display_order' => 30,
            'is_default' => true,
        ]);
        $wipe = WorkflowProfile::factory()->create([
            'name' => 'Laptop wipen',
            'display_order' => 0,
        ]);
        $windows = WorkflowProfile::factory()->create([
            'name' => 'Windows installeren en updaten',
            'display_order' => 10,
        ]);

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('settings.workflow-profiles.index'));

        $response->assertOk();
        $response->assertSeeInOrder([
            'data-workflow-profile-id="' . $wipe->id . '"',
            'data-workflow-profile-id="' . $windows->id . '"',
            'data-workflow-profile-id="' . $diagnostics->id . '"',
        ], false);
        $response->assertSee('data-workflow-profile-drag-handle', false);
        $response->assertSee(route('settings.workflow-profiles.reorder'), false);
        $response->assertSeeInOrder([
            '1. Laptop wipen',
            '2. Windows installeren en updaten',
            '3. Standard Diagnostics',
        ]);
    }

    public function test_settings_index_links_to_profiles_and_items_separately(): void
    {
        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('settings.index'));

        $response->assertOk();
        $response->assertSee('Workflow Profiles');
        $response->assertSee('Workflow Items');
        $response->assertSee(route('settings.workflow-profiles.index'), false);
        $response->assertSee(route('settings.testtypes.index'), false);
    }

    public function test_admin_can_open_profile_item_subpage(): void
    {
        $profile = WorkflowProfile::factory()->create([
            'name' => 'Standard Diagnostics',
        ]);
        $included = TestType::factory()->create([
            'name' => 'Keyboard',
            'slug' => 'keyboard',
        ]);
        $available = TestType::factory()->create([
            'name' => 'Camera',
            'slug' => 'camera',
        ]);
        $profileItem = WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $profile->id,
            'workflow_item_id' => $included->id,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('settings.workflow-profiles.items.edit', $profile));

        $response->assertOk();
        $response->assertSee('Standard Diagnostics');
        $response->assertSee('Included Items');
        $response->assertSee('Available Items');
        $response->assertSee('Keyboard');
        $response->assertSee('Camera');
        $response->assertSee('data-profile-item-reorder-body', false);
        $response->assertSee('data-profile-item-id="' . $profileItem->id . '"', false);
        $response->assertSee('name="items[' . $included->id . '][remove]"', false);
        $response->assertSee('name="items[' . $available->id . '][enabled]"', false);
        $response->assertDontSee('<th>Use</th>', false);
        $response->assertSee(route('settings.workflow-profiles.items.update', $profile), false);
        $response->assertSee(route('settings.workflow-profiles.items.reorder', $profile), false);
    }

    public function test_admin_can_update_profile_items_from_subpage(): void
    {
        $profile = WorkflowProfile::factory()->create();
        $existing = TestType::factory()->create([
            'name' => 'Old Item',
        ]);
        $new = TestType::factory()->create([
            'name' => 'New Item',
            'is_required' => false,
            'result_label_mode' => WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE,
        ]);
        WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $profile->id,
            'workflow_item_id' => $existing->id,
            'sort_order' => 0,
            'is_required' => true,
        ]);

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->put(route('settings.workflow-profiles.items.update', $profile), [
                'items' => [
                    $existing->id => [
                        'enabled' => 1,
                        'remove' => 1,
                        'sort_order' => 0,
                        'is_required' => 1,
                        'result_label_mode' => WorkflowProfileItem::LABEL_MODE_PASS_FAIL,
                    ],
                    $new->id => [
                        'enabled' => 1,
                        'sort_order' => 0,
                    ],
                ],
            ]);

        $response->assertRedirect(route('settings.workflow-profiles.items.edit', $profile));

        $this->assertDatabaseMissing('workflow_profile_items', [
            'workflow_profile_id' => $profile->id,
            'workflow_item_id' => $existing->id,
        ]);
        $this->assertDatabaseHas('workflow_profile_items', [
            'workflow_profile_id' => $profile->id,
            'workflow_item_id' => $new->id,
            'sort_order' => 0,
            'is_required' => 0,
            'result_label_mode' => WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE,
        ]);
    }

    public function test_admin_can_reorder_profile_items(): void
    {
        $profile = WorkflowProfile::factory()->create();
        $first = WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $profile->id,
            'sort_order' => 0,
        ]);
        $second = WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $profile->id,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->patch(route('settings.workflow-profiles.items.reorder', $profile), [
                'order' => [$second->id, $first->id],
            ]);

        $response->assertOk()->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('workflow_profile_items', [
            'id' => $second->id,
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('workflow_profile_items', [
            'id' => $first->id,
            'sort_order' => 1,
        ]);
    }

    public function test_admin_can_drag_profiles_into_a_persisted_order(): void
    {
        $first = WorkflowProfile::factory()->create(['display_order' => 0]);
        $second = WorkflowProfile::factory()->create(['display_order' => 1]);
        $third = WorkflowProfile::factory()->create(['display_order' => 2]);

        $this->actingAs(User::factory()->superuser()->create())
            ->patchJson(route('settings.workflow-profiles.reorder'), [
                'order' => [$third->id, $first->id, $second->id],
            ])
            ->assertOk()
            ->assertJson(['status' => 'ok']);

        $this->assertSame(0, $third->fresh()->display_order);
        $this->assertSame(1, $first->fresh()->display_order);
        $this->assertSame(2, $second->fresh()->display_order);
        $this->assertSame(
            [$third->id, $first->id, $second->id],
            WorkflowProfile::query()
                ->ordered()
                ->limit(3)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all()
        );
    }

    public function test_profile_reorder_safely_appends_profiles_missing_from_a_stale_page_payload(): void
    {
        $first = WorkflowProfile::factory()->create(['display_order' => 0]);
        $second = WorkflowProfile::factory()->create(['display_order' => 1]);

        $this->actingAs(User::factory()->superuser()->create())
            ->patchJson(route('settings.workflow-profiles.reorder'), [
                'order' => [$second->id],
            ])
            ->assertOk()
            ->assertJson(['status' => 'ok']);

        $orderedIds = WorkflowProfile::query()
            ->ordered()
            ->pluck('id')
            ->map(fn ($id): int => (int) $id);

        $this->assertSame($second->id, $orderedIds->first());
        $this->assertTrue($orderedIds->search($first->id) > $orderedIds->search($second->id));
    }

    public function test_admin_can_change_dependencies_and_repeat_policy_after_creation(): void
    {
        $prerequisite = WorkflowProfile::factory()->create(['name' => 'Diagnostics']);
        $profile = WorkflowProfile::factory()->create(['name' => 'Cleaning']);

        $this->actingAs(User::factory()->superuser()->create())
            ->put(route('settings.workflow-profiles.update', $profile), [
                'name' => $profile->name,
                'slug' => $profile->slug,
                'description' => $profile->description,
                'display_order' => 20,
                'repeat_policy' => WorkflowProfile::REPEAT_OVERRIDE_REQUIRED,
                'execution_level' => WorkflowProfile::EXECUTION_SENIOR,
                'is_active' => 1,
                'is_default' => 0,
                'blocks_sale_readiness' => 0,
                'dependency_profile_ids' => [$prerequisite->id],
            ])
            ->assertRedirect(route('settings.workflow-profiles.index'));

        $this->assertDatabaseHas('workflow_profile_dependencies', [
            'workflow_profile_id' => $profile->id,
            'prerequisite_workflow_profile_id' => $prerequisite->id,
        ]);
        $this->assertSame(WorkflowProfile::REPEAT_OVERRIDE_REQUIRED, $profile->fresh()->repeat_policy);
        $this->assertSame(WorkflowProfile::EXECUTION_SENIOR, $profile->fresh()->execution_level);
    }

    public function test_dependency_defaults_to_required_items_passed_or_done(): void
    {
        $prerequisite = WorkflowProfile::factory()->create(['name' => 'Laptop wipen']);
        $profile = WorkflowProfile::factory()->create(['name' => 'Windows installeren en updaten']);

        $this->actingAs(User::factory()->superuser()->create())
            ->put(route('settings.workflow-profiles.update', $profile), [
                'name' => $profile->name,
                'slug' => $profile->slug,
                'display_order' => 1,
                'repeat_policy' => WorkflowProfile::REPEAT_OVERRIDE_REQUIRED,
                'is_active' => 1,
                'is_default' => 0,
                'blocks_sale_readiness' => 0,
                'dependency_profile_ids' => [$prerequisite->id],
            ])
            ->assertRedirect(route('settings.workflow-profiles.index'));

        $this->assertDatabaseHas('workflow_profile_dependencies', [
            'workflow_profile_id' => $profile->id,
            'prerequisite_workflow_profile_id' => $prerequisite->id,
        ]);
    }

    public function test_profile_item_requirement_can_be_changed_per_workflow(): void
    {
        $profile = WorkflowProfile::factory()->create();
        $item = TestType::factory()->create(['is_required' => true]);
        $profileItem = WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $profile->id,
            'workflow_item_id' => $item->id,
            'is_required' => false,
            'result_label_mode' => WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE,
        ]);

        $page = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('settings.workflow-profiles.items.edit', $profile));

        $page->assertOk();
        $page->assertSee('name="items[' . $item->id . '][is_required]"', false);
        $page->assertSee('value="done_not_done" selected', false);
        $page->assertDontSee(
            'name="items[' . $item->id . '][is_required]" value="1" checked',
            false
        );

        $this->actingAs(User::factory()->superuser()->create())
            ->put(route('settings.workflow-profiles.items.update', $profile), [
                'items' => [
                    $item->id => [
                        'enabled' => 1,
                        'sort_order' => 0,
                        'is_required' => 1,
                        'result_label_mode' => WorkflowProfileItem::LABEL_MODE_PASS_FAIL,
                    ],
                ],
            ])
            ->assertRedirect(route('settings.workflow-profiles.items.edit', $profile));

        $this->assertTrue($profileItem->fresh()->is_required);
        $this->assertSame(
            WorkflowProfileItem::LABEL_MODE_PASS_FAIL,
            $profileItem->fresh()->result_label_mode
        );
    }

    public function test_admin_cannot_create_a_circular_dependency(): void
    {
        $first = WorkflowProfile::factory()->create(['name' => 'First']);
        $second = WorkflowProfile::factory()->create(['name' => 'Second']);
        $first->prerequisites()->sync([$second->id]);

        $this->actingAs(User::factory()->superuser()->create())
            ->put(route('settings.workflow-profiles.update', $second), [
                'name' => $second->name,
                'slug' => $second->slug,
                'display_order' => 2,
                'repeat_policy' => WorkflowProfile::REPEAT_OVERRIDE_REQUIRED,
                'is_active' => 1,
                'is_default' => 0,
                'blocks_sale_readiness' => 0,
                'dependency_profile_ids' => [$first->id],
            ])
            ->assertSessionHasErrors('dependency_profile_ids');

        $this->assertDatabaseMissing('workflow_profile_dependencies', [
            'workflow_profile_id' => $second->id,
            'prerequisite_workflow_profile_id' => $first->id,
        ]);
    }

    public function test_admin_must_disconnect_dependents_before_deactivating_a_prerequisite(): void
    {
        $prerequisite = WorkflowProfile::factory()->create(['name' => 'Diagnostics']);
        $dependent = WorkflowProfile::factory()->create(['name' => 'Pre-Sale Check']);
        $dependent->prerequisites()->sync([$prerequisite->id]);

        $this->actingAs(User::factory()->superuser()->create())
            ->put(route('settings.workflow-profiles.update', $prerequisite), [
                'name' => $prerequisite->name,
                'slug' => $prerequisite->slug,
                'display_order' => 0,
                'repeat_policy' => WorkflowProfile::REPEAT_OVERRIDE_REQUIRED,
                'execution_level' => WorkflowProfile::EXECUTION_OPERATOR,
                'is_active' => 0,
                'is_default' => 0,
                'blocks_sale_readiness' => 0,
            ])
            ->assertSessionHasErrors('is_active');

        $this->assertTrue($prerequisite->fresh()->is_active);
    }
}
