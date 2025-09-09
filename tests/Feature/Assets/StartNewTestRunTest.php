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
        TestType::factory()->count(3)->create();
        $user = User::factory()->superuser()->create();

        $response = $this->actingAs($user)->post(route('test-runs.store', $asset->id));
        $run = TestRun::where('asset_id', $asset->id)->latest()->first();

        $response->assertRedirect(route('test-results.edit', [$asset->id, $run->id]));
        $this->assertNotNull($run->started_at);
        $this->assertCount(TestType::count(), $run->results);
        $this->assertEquals($asset->sku_id, $run->sku_id);
        $run->results->each(function ($result) {
            $this->assertEquals(TestResult::STATUS_NVT, $result->status);
            $this->assertNull($result->note);
        });
    }

    public function test_phone_category_skips_test_generation(): void
    {
        $category = Category::factory()->assetMobileCategory()->create();
        $model = AssetModel::factory()->create(['category_id' => $category->id]);
        $asset = Asset::factory()->create(['model_id' => $model->id]);
        TestType::factory()->count(3)->create();
        $user = User::factory()->superuser()->create();

        $this->actingAs($user)->post(route('test-runs.store', $asset->id));
        $run = TestRun::where('asset_id', $asset->id)->latest()->first();

        $this->assertNotNull($run);
        $this->assertCount(0, $run->results);
    }
}
