<?php

namespace Tests\Feature\Settings;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Category;
use App\Models\ComponentDefinition;
use App\Models\TestType;
use App\Models\User;
use App\Models\WorkflowProfileItem;
use Tests\TestCase;

class ManageTestTypesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_admin_can_create_test_type_with_slug_generated_from_name(): void
    {
        $user = User::factory()->superuser()->create();

        $response = $this->actingAs($user)
            ->post(route('settings.testtypes.store'), [
                'name' => 'Battery / Health',
                'tooltip' => 'Checks battery condition',
                'is_required' => 1,
                'result_label_mode' => WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE,
            ]);

        $response->assertRedirect(route('settings.testtypes.index'));
        $this->assertDatabaseHas('workflow_items', [
            'name' => 'Battery / Health',
            'slug' => 'battery-health',
            'result_label_mode' => WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE,
        ]);
    }

    public function test_admin_can_create_workflow_item_with_component_applicability(): void
    {
        $user = User::factory()->superuser()->create();
        $assetCategory = Category::factory()->assetLaptopCategory()->create();
        $componentCategory = Category::factory()->forComponents()->create(['name' => 'Ports']);
        $definition = ComponentDefinition::factory()->create([
            'category_id' => $componentCategory->id,
            'name' => 'USB-C Port',
        ]);

        $response = $this->actingAs($user)
            ->post(route('settings.testtypes.store'), [
                'name' => 'USB Ports',
                'category_ids' => [$assetCategory->id],
                'component_category_ids' => [$componentCategory->id],
                'component_definition_ids' => [$definition->id],
                'applies_to_all' => 1,
                'is_required' => 1,
            ]);

        $response->assertRedirect(route('settings.testtypes.index'));

        $item = TestType::query()->where('slug', 'usb-ports')->firstOrFail();
        $this->assertTrue((bool) $item->applies_to_all);
        $this->assertSame([$assetCategory->id], $item->categories()->pluck('categories.id')->all());
        $this->assertSame([$componentCategory->id], $item->componentCategories()->pluck('categories.id')->all());
        $this->assertSame([$definition->id], $item->componentDefinitions()->pluck('component_definitions.id')->all());
    }

    public function test_workflow_item_modal_selects_are_full_width(): void
    {
        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('settings.testtypes.index'));

        $response->assertOk();
        $response->assertSee('#create-testtype-modal .select2-container', false);
        $response->assertSee('[id^="edit-testtype-"][id$="-modal"] .select2-container', false);
        $response->assertSee("width: '100%'", false);
    }

    public function test_admin_create_suffixes_generated_slug_when_name_collides(): void
    {
        $user = User::factory()->superuser()->create();
        TestType::factory()->create([
            'name' => 'Battery Health',
            'slug' => 'battery-health',
        ]);

        $response = $this->actingAs($user)
            ->post(route('settings.testtypes.store'), [
                'name' => 'Battery Health',
                'is_required' => 1,
            ]);

        $response->assertRedirect(route('settings.testtypes.index'));
        $this->assertDatabaseHas('workflow_items', [
            'name' => 'Battery Health',
            'slug' => 'battery-health-2',
        ]);
    }

    public function test_admin_can_update_tooltip(): void
    {
        $user = User::factory()->superuser()->create();
        $type = TestType::factory()->create(['tooltip' => 'Old']);

        $response = $this->actingAs($user)
            ->put(route('settings.testtypes.update', $type), [
                'name' => $type->name,
                'slug' => $type->slug,
                'tooltip' => 'New tip',
                'is_required' => 1,
                'result_label_mode' => WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE,
            ]);

        $response->assertRedirect(route('settings.testtypes.index'));
        $this->assertDatabaseHas('workflow_items', [
            'id' => $type->id,
            'tooltip' => 'New tip',
            'result_label_mode' => WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE,
        ]);
    }

    public function test_admin_update_uses_name_for_slug_when_manual_override_is_off(): void
    {
        $user = User::factory()->superuser()->create();
        $type = TestType::factory()->create([
            'name' => 'Camera',
            'slug' => 'camera',
        ]);

        $response = $this->actingAs($user)
            ->put(route('settings.testtypes.update', $type), [
                'name' => 'Battery / Health',
                'tooltip' => $type->tooltip,
                'is_required' => 1,
                'manual_slug_override' => 0,
            ]);

        $response->assertRedirect(route('settings.testtypes.index'));
        $this->assertDatabaseHas('workflow_items', [
            'id' => $type->id,
            'name' => 'Battery / Health',
            'slug' => 'battery-health',
        ]);
    }

    public function test_admin_can_manually_override_slug_and_it_is_normalized_with_suffix(): void
    {
        $user = User::factory()->superuser()->create();
        TestType::factory()->create([
            'name' => 'Battery Health',
            'slug' => 'battery-health',
        ]);
        $type = TestType::factory()->create([
            'name' => 'Camera',
            'slug' => 'camera',
        ]);

        $response = $this->actingAs($user)
            ->put(route('settings.testtypes.update', $type), [
                'name' => 'Camera',
                'slug' => 'Battery ### Health',
                'tooltip' => $type->tooltip,
                'is_required' => 1,
                'manual_slug_override' => 1,
            ]);

        $response->assertRedirect(route('settings.testtypes.index'));
        $this->assertDatabaseHas('workflow_items', [
            'id' => $type->id,
            'slug' => 'battery-health-2',
        ]);
    }

    public function test_admin_can_reorder_test_types(): void
    {
        $user = User::factory()->superuser()->create();
        $first = TestType::factory()->create([
            'name' => 'First',
            'display_order' => 0,
        ]);
        $second = TestType::factory()->create([
            'name' => 'Second',
            'display_order' => 1,
        ]);
        $third = TestType::factory()->create([
            'name' => 'Third',
            'display_order' => 2,
        ]);

        $response = $this->actingAs($user)->patch(route('settings.testtypes.reorder'), [
            'order' => [$third->id, $first->id, $second->id],
        ]);

        $response->assertOk()->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('workflow_items', [
            'id' => $third->id,
            'display_order' => 0,
        ]);
        $this->assertDatabaseHas('workflow_items', [
            'id' => $first->id,
            'display_order' => 1,
        ]);
        $this->assertDatabaseHas('workflow_items', [
            'id' => $second->id,
            'display_order' => 2,
        ]);
    }
}
