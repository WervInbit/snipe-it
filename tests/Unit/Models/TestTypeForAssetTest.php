<?php

namespace Tests\Unit\Models;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\ModelNumberAttribute;
use App\Models\TestType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestTypeForAssetTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_attribute_tests_for_asset(): void
    {
        $category = Category::factory()->create();
        $definition = AttributeDefinition::create([
            'key' => 'battery_health',
            'label' => 'Battery Health',
            'datatype' => AttributeDefinition::DATATYPE_INT,
            'required_for_category' => true,
            'needs_test' => true,
            'allow_custom_values' => false,
            'allow_asset_override' => false,
        ]);
        $definition->categories()->sync([$category->id]);

        $model = AssetModel::factory()->create([
            'category_id' => $category->id,
        ]);

        ModelNumberAttribute::create([
            'model_id' => $model->id,
            'attribute_definition_id' => $definition->id,
            'value' => '95',
        ]);

        $asset = Asset::factory()->create([
            'model_id' => $model->id,
        ]);

        $types = TestType::forAsset($asset)->get();

        $this->assertCount(1, $types);
        $this->assertSame('attribute-' . $definition->id, $types->first()->slug);
    }

    public function test_returns_empty_collection_when_no_attributes_require_tests(): void
    {
        $category = Category::factory()->create();
        $model = AssetModel::factory()->create(['category_id' => $category->id]);
        $asset = Asset::factory()->create(['model_id' => $model->id]);

        $this->assertCount(0, TestType::forAsset($asset)->get());
    }
}
