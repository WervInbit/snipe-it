<?php

namespace Tests\Unit\Models;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Sku;
use App\Models\TestType;
use Tests\TestCase;

class TestTypeForAssetTest extends TestCase
{
    public function test_returns_tests_for_laptop_and_desktop_categories(): void
    {
        $category = Category::factory()->assetLaptopCategory()->create();
        $model = AssetModel::factory()->create(['category_id' => $category->id]);
        $sku = Sku::factory()->create(['model_id' => $model->id]);
        $asset = Asset::factory()->create(['model_id' => $model->id, 'sku_id' => $sku->id]);
        TestType::factory()->count(2)->create(['category' => 'computer']);

        $this->assertCount(2, TestType::forAsset($asset)->get());
    }

    public function test_skips_tests_for_phone_category(): void
    {
        $category = Category::factory()->assetMobileCategory()->create();
        $model = AssetModel::factory()->create(['category_id' => $category->id]);
        $asset = Asset::factory()->create(['model_id' => $model->id]);
        TestType::factory()->count(2)->create(['category' => 'computer']);

        $this->assertCount(0, TestType::forAsset($asset)->get());
    }
}
