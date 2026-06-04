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
}
