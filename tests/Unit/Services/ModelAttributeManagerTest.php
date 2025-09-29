<?php

namespace Tests\Unit\Services;

use App\Models\Asset;
use App\Models\AssetAttributeOverride;
use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\ModelNumberAttribute;
use App\Services\ModelAttributes\ModelAttributeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ModelAttributeManagerTest extends TestCase
{
    use RefreshDatabase;

    private function makeAttributeDefinition(Category $category, array $overrides = []): AttributeDefinition
    {
        $definition = AttributeDefinition::create(array_merge([
            'key' => 'storage_capacity_' . uniqid(),
            'label' => 'Storage Capacity',
            'datatype' => AttributeDefinition::DATATYPE_BOOL,
            'unit' => null,
            'required_for_category' => true,
            'needs_test' => true,
            'allow_custom_values' => false,
            'allow_asset_override' => true,
        ], $overrides));

        $definition->categories()->sync([$category->id]);

        return $definition;
    }

    public function test_save_model_attributes_persists_values(): void
    {
        $category = Category::factory()->create();
        $definition = $this->makeAttributeDefinition($category);
        $model = AssetModel::factory()->create(['category_id' => $category->id]);
        $modelNumber = $model->ensurePrimaryModelNumber();

        /** @var ModelAttributeManager $manager */
        $manager = app(ModelAttributeManager::class);

        $manager->saveModelAttributes($modelNumber, [
            $definition->id => '1',
        ]);

        $record = ModelNumberAttribute::where('model_number_id', $modelNumber->id)
            ->where('attribute_definition_id', $definition->id)
            ->first();

        $this->assertNotNull($record);
        $this->assertSame('1', $record->value);
        $this->assertSame('1', $record->raw_value);
    }

    public function test_save_model_attributes_requires_required_fields(): void
    {
        $this->expectException(ValidationException::class);

        $category = Category::factory()->create();
        $definition = $this->makeAttributeDefinition($category);
        $model = AssetModel::factory()->create(['category_id' => $category->id]);
        $modelNumber = $model->ensurePrimaryModelNumber();

        /** @var ModelAttributeManager $manager */
        $manager = app(ModelAttributeManager::class);

        $manager->saveModelAttributes($modelNumber, [
            $definition->id => null,
        ]);
    }

    public function test_save_asset_overrides_persists_records_when_allowed(): void
    {
        $category = Category::factory()->create();
        $definition = $this->makeAttributeDefinition($category, [
            'allow_asset_override' => true,
        ]);
        $model = AssetModel::factory()->create(['category_id' => $category->id]);
        $modelNumber = $model->ensurePrimaryModelNumber();
        $asset = Asset::factory()->create([
            'model_id' => $model->id,
            'model_number_id' => $modelNumber->id,
        ]);

        /** @var ModelAttributeManager $manager */
        $manager = app(ModelAttributeManager::class);

        $manager->saveAssetOverrides($asset, [
            $definition->id => '0',
        ]);

        $override = AssetAttributeOverride::where('asset_id', $asset->id)
            ->where('attribute_definition_id', $definition->id)
            ->first();

        $this->assertNotNull($override);
        $this->assertSame('0', $override->value);
        $this->assertSame('0', $override->raw_value);
    }

    public function test_save_asset_overrides_ignores_when_not_allowed(): void
    {
        $category = Category::factory()->create();
        $definition = $this->makeAttributeDefinition($category, [
            'allow_asset_override' => false,
        ]);
        $model = AssetModel::factory()->create(['category_id' => $category->id]);
        $modelNumber = $model->ensurePrimaryModelNumber();
        $asset = Asset::factory()->create([
            'model_id' => $model->id,
            'model_number_id' => $modelNumber->id,
        ]);

        /** @var ModelAttributeManager $manager */
        $manager = app(ModelAttributeManager::class);

        $manager->saveAssetOverrides($asset, [
            $definition->id => '1',
        ]);

        $this->assertDatabaseMissing('asset_attribute_overrides', [
            'asset_id' => $asset->id,
            'attribute_definition_id' => $definition->id,
        ]);
    }
}
