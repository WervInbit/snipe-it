<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\TestRun;
use App\Models\TestResult;
use App\Models\TestType;
use App\Models\User;
use Tests\TestCase;

class StartNewTestRunTest extends TestCase
{
    public function test_start_new_run_creates_results_and_redirects(): void
    {
        $asset = Asset::factory()->laptopMbp()->create();
        $categoryId = $asset->model?->category_id;
        $types = TestType::factory()->count(3)->create();

        if ($categoryId) {
            $types->each(fn (TestType $type) => $type->categories()->sync([$categoryId]));
        }
        $user = User::factory()->superuser()->create();

        $response = $this->actingAs($user)->post(route('test-runs.store', $asset->id));
        $run = TestRun::where('asset_id', $asset->id)->latest()->first();

        $response->assertRedirect(route('test-results.active', [$asset->id]));
        $this->assertNotNull($run->started_at);
        $this->assertCount(TestType::count(), $run->results);
        $this->assertEquals($asset->model_number_id, $run->model_number_id);
        $run->results->each(function ($result) {
            $this->assertEquals(TestResult::STATUS_NVT, $result->status);
            $this->assertNull($result->note);
        });
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
        $types = TestType::factory()->count(3)->create();
        $types->each(fn (TestType $type) => $type->categories()->sync([$otherCategory->id]));
        $user = User::factory()->superuser()->create();

        $this->actingAs($user)->post(route('test-runs.store', $asset->id));
        $run = TestRun::where('asset_id', $asset->id)->latest()->first();

        $this->assertNotNull($run);
        $this->assertCount(0, $run->results);
    }
}

