<?php

namespace Tests\Feature\Assets;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\ComponentDefinition;
use App\Models\ModelNumberComponentTemplate;
use App\Models\TestRun;
use App\Models\TestResult;
use App\Models\TestType;
use App\Models\User;
use App\Models\WorkflowProfile;
use App\Models\WorkflowProfileItem;
use Tests\TestCase;

class StartNewTestRunTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_start_new_run_creates_results_and_redirects(): void
    {
        $asset = Asset::factory()->laptopMbp()->create();
        $categoryId = $asset->model?->category_id;
        $profile = WorkflowProfile::factory()->create(['is_default' => true]);
        $types = TestType::factory()->count(3)->create();

        if ($categoryId) {
            $types->each(fn (TestType $type) => $type->categories()->sync([$categoryId]));
        }
        $types->values()->each(fn (TestType $type, int $index) => WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $profile->id,
            'workflow_item_id' => $type->id,
            'sort_order' => $index,
        ]));
        $user = User::factory()->superuser()->create();

        $response = $this->actingAs($user)->post(route('test-runs.store', $asset->id), [
            'workflow_profile_id' => $profile->id,
        ]);
        $run = TestRun::where('asset_id', $asset->id)->latest()->first();

        $response->assertRedirect(route('test-results.active', ['asset' => $asset->id, 'run' => $run->id]));
        $this->assertNotNull($run->started_at);
        $this->assertEquals($profile->id, $run->workflow_profile_id);
        $this->assertCount($types->count(), $run->results);
        $this->assertEquals($asset->model_number_id, $run->model_number_id);
        $run->results->each(function ($result) {
            $this->assertEquals(TestResult::STATUS_NVT, $result->status);
            $this->assertNull($result->note);
        });
    }

    public function test_start_new_run_requires_profile_selection(): void
    {
        $asset = Asset::factory()->laptopMbp()->create();
        $profile = WorkflowProfile::factory()->create(['is_default' => true]);
        WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $profile->id,
            'workflow_item_id' => TestType::factory()->create()->id,
        ]);
        $user = User::factory()->superuser()->create();

        $this->actingAs($user)
            ->post(route('test-runs.store', $asset->id))
            ->assertSessionHasErrors('workflow_profile_id');

        $this->assertNull(TestRun::query()->where('asset_id', $asset->id)->latest()->first());
    }

    public function test_user_with_asset_view_and_test_execute_can_start_new_run_without_asset_edit(): void
    {
        $asset = Asset::factory()->laptopMbp()->create();
        $categoryId = $asset->model?->category_id;
        $profile = WorkflowProfile::factory()->create(['is_default' => true]);
        $type = TestType::factory()->create(['name' => 'Visual Check']);

        if ($categoryId) {
            $type->categories()->sync([$categoryId]);
        }

        WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $profile->id,
            'workflow_item_id' => $type->id,
        ]);

        $user = User::factory()->create([
            'permissions' => json_encode([
                'assets.view' => '1',
                'tests.execute' => '1',
            ]),
        ]);

        $this->actingAs($user)
            ->post(route('test-runs.store', $asset->id), [
                'workflow_profile_id' => $profile->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('workflow_runs', [
            'asset_id' => $asset->id,
            'workflow_profile_id' => $profile->id,
        ]);
    }

    public function test_category_scoped_tests_skip_other_categories(): void
    {
        $category = Category::factory()->assetMobileCategory()->create();
        $model = AssetModel::factory()->create(['category_id' => $category->id]);
        $asset = Asset::factory()->create([
            'model_id' => $model->id,
            'model_number_id' => $model->ensurePrimaryModelNumber()->id,
        ]);
        $otherCategory = Category::factory()->assetLaptopCategory()->create();
        $profile = WorkflowProfile::factory()->create(['is_default' => true]);
        $types = TestType::factory()->count(3)->create();
        $types->each(fn (TestType $type) => $type->categories()->sync([$otherCategory->id]));
        $types->values()->each(fn (TestType $type, int $index) => WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $profile->id,
            'workflow_item_id' => $type->id,
            'sort_order' => $index,
        ]));
        $user = User::factory()->superuser()->create();

        $this->actingAs($user)
            ->post(route('test-runs.store', $asset->id), [
                'workflow_profile_id' => $profile->id,
            ])
            ->assertSessionHasErrors('workflow_profile_id');
        $run = TestRun::where('asset_id', $asset->id)->latest()->first();

        $this->assertNull($run);
    }

    public function test_start_new_run_uses_profile_item_order_for_created_results(): void
    {
        $asset = Asset::factory()->laptopMbp()->create();
        $categoryId = $asset->model?->category_id;
        $profile = WorkflowProfile::factory()->create(['is_default' => true]);

        $first = TestType::factory()->create(['name' => 'First', 'display_order' => 0]);
        $second = TestType::factory()->create(['name' => 'Second', 'display_order' => 1]);
        $third = TestType::factory()->create(['name' => 'Third', 'display_order' => 2]);

        if ($categoryId) {
            collect([$first, $second, $third])->each(
                fn (TestType $type) => $type->categories()->sync([$categoryId])
            );
        }

        $first->update(['display_order' => 2]);
        $second->update(['display_order' => 0]);
        $third->update(['display_order' => 1]);

        WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $profile->id,
            'workflow_item_id' => $first->id,
            'sort_order' => 2,
        ]);
        WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $profile->id,
            'workflow_item_id' => $second->id,
            'sort_order' => 0,
        ]);
        WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $profile->id,
            'workflow_item_id' => $third->id,
            'sort_order' => 1,
        ]);

        $user = User::factory()->superuser()->create();
        $this->actingAs($user)->post(route('test-runs.store', $asset->id), [
            'workflow_profile_id' => $profile->id,
        ]);

        $run = TestRun::query()->where('asset_id', $asset->id)->latest()->firstOrFail();
        $orderedTypeIds = $run->results()->pluck('workflow_item_id')->map(fn ($id) => (int) $id)->all();

        $this->assertSame([$second->id, $third->id, $first->id], $orderedTypeIds);
    }

    public function test_start_new_run_only_creates_component_applicable_profile_items(): void
    {
        $asset = Asset::factory()->laptopMbp()->create();
        $modelNumber = $asset->modelNumber ?: $asset->model->ensurePrimaryModelNumber();
        $asset->forceFill(['model_number_id' => $modelNumber->id])->save();
        $profile = WorkflowProfile::factory()->create(['is_default' => true]);

        $expectedDefinition = ComponentDefinition::factory()->create(['name' => 'HDMI Port']);
        $missingDefinition = ComponentDefinition::factory()->create(['name' => 'VGA Port']);
        ModelNumberComponentTemplate::factory()->create([
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $expectedDefinition->id,
        ]);

        $expectedItem = TestType::factory()->create(['name' => 'HDMI', 'slug' => 'hdmi']);
        $expectedItem->componentDefinitions()->sync([$expectedDefinition->id]);
        $missingItem = TestType::factory()->create(['name' => 'VGA', 'slug' => 'vga']);
        $missingItem->componentDefinitions()->sync([$missingDefinition->id]);

        WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $profile->id,
            'workflow_item_id' => $expectedItem->id,
            'sort_order' => 0,
        ]);
        WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $profile->id,
            'workflow_item_id' => $missingItem->id,
            'sort_order' => 1,
        ]);

        $user = User::factory()->superuser()->create();

        $this->actingAs($user)->post(route('test-runs.store', $asset->id), [
            'workflow_profile_id' => $profile->id,
        ]);

        $run = TestRun::query()->where('asset_id', $asset->id)->latest()->firstOrFail();
        $this->assertSame([$expectedItem->id], $run->results()->pluck('workflow_item_id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_start_new_run_uses_workflow_item_defaults_for_result_settings(): void
    {
        $asset = Asset::factory()->laptopMbp()->create();
        $categoryId = $asset->model?->category_id;
        $profile = WorkflowProfile::factory()->create(['is_default' => true]);
        $item = TestType::factory()->create([
            'name' => 'Task Item',
            'slug' => 'task-item',
            'is_required' => false,
            'result_label_mode' => WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE,
        ]);

        if ($categoryId) {
            $item->categories()->sync([$categoryId]);
        }

        WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $profile->id,
            'workflow_item_id' => $item->id,
            'is_required' => true,
            'result_label_mode' => WorkflowProfileItem::LABEL_MODE_PASS_FAIL,
        ]);

        $this->actingAs(User::factory()->superuser()->create())->post(route('test-runs.store', $asset->id), [
            'workflow_profile_id' => $profile->id,
        ]);

        $run = TestRun::query()->where('asset_id', $asset->id)->latest()->firstOrFail();

        $this->assertDatabaseHas('workflow_results', [
            'workflow_run_id' => $run->id,
            'workflow_item_id' => $item->id,
            'is_required' => 0,
            'result_label_mode' => WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE,
        ]);
    }

    public function test_start_new_run_can_include_extra_manual_items(): void
    {
        $asset = Asset::factory()->laptopMbp()->create();
        $categoryId = $asset->model?->category_id;
        $profile = WorkflowProfile::factory()->create(['is_default' => true]);
        $profileItem = TestType::factory()->create(['name' => 'Profile Item', 'slug' => 'profile-item']);
        $extraItem = TestType::factory()->create([
            'name' => 'One Off Check',
            'slug' => 'one-off-check',
            'result_label_mode' => WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE,
        ]);

        if ($categoryId) {
            $profileItem->categories()->sync([$categoryId]);
        }

        WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $profile->id,
            'workflow_item_id' => $profileItem->id,
            'sort_order' => 0,
        ]);

        $user = User::factory()->superuser()->create();

        $this->actingAs($user)->post(route('test-runs.store', $asset->id), [
            'workflow_profile_id' => $profile->id,
            'extra_workflow_item_ids' => [$extraItem->id],
        ]);

        $run = TestRun::query()->where('asset_id', $asset->id)->latest()->firstOrFail();
        $this->assertSame(
            [$profileItem->id, $extraItem->id],
            $run->results()->orderBy('sort_order')->pluck('workflow_item_id')->map(fn ($id) => (int) $id)->all()
        );
        $this->assertDatabaseHas('workflow_results', [
            'workflow_run_id' => $run->id,
            'workflow_item_id' => $extraItem->id,
            'result_label_mode' => WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE,
        ]);
    }
}

