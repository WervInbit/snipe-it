<?php

namespace Tests\Unit\Services;

use App\Models\Asset;
use App\Models\AssetAttributeOverride;
use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\ModelNumber;
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
            'allow_custom_values' => false,
            'allow_asset_override' => true,
        ], $overrides));

        $definition->categories()->sync([$category->id]);

        return $definition;
    }

    private function assignAttribute(ModelNumber $modelNumber, AttributeDefinition $definition, array $overrides = []): ModelNumberAttribute
    {
        return ModelNumberAttribute::create(array_merge([
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $definition->id,
            'display_order' => 0,
            'value' => null,
            'raw_value' => null,
            'attribute_option_id' => null,
        ], $overrides));
    }

    public function test_save_model_attributes_persists_values(): void
    {
        $category = Category::factory()->create();
        $definition = $this->makeAttributeDefinition($category);
        $model = AssetModel::factory()->create(['category_id' => $category->id]);
        $modelNumber = $model->ensurePrimaryModelNumber();
        $this->assignAttribute($modelNumber, $definition);

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
        $this->assignAttribute($modelNumber, $definition);

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
        $this->assignAttribute($modelNumber, $definition);
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
        $this->assignAttribute($modelNumber, $definition);
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

    public function test_sync_model_number_assignments_creates_and_reorders_attributes(): void
    {
        $category = Category::factory()->create();
        $first = $this->makeAttributeDefinition($category, ['key' => 'first_attr', 'label' => 'First']);
        $second = $this->makeAttributeDefinition($category, ['key' => 'second_attr', 'label' => 'Second']);
        $third = $this->makeAttributeDefinition($category, ['key' => 'third_attr', 'label' => 'Third']);
        $model = AssetModel::factory()->create(['category_id' => $category->id]);
        $modelNumber = $model->ensurePrimaryModelNumber();

        $this->assignAttribute($modelNumber, $first, ['display_order' => 0]);
        $this->assignAttribute($modelNumber, $second, ['display_order' => 1]);

        /** @var ModelAttributeManager $manager */
        $manager = app(ModelAttributeManager::class);

        $manager->syncModelNumberAssignments($modelNumber, [$second->id, $third->id, $first->id]);

        $this->assertDatabaseHas('model_number_attributes', [
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $second->id,
            'display_order' => 0,
        ]);

        $this->assertDatabaseHas('model_number_attributes', [
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $third->id,
            'display_order' => 1,
        ]);

        $this->assertDatabaseHas('model_number_attributes', [
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $first->id,
            'display_order' => 2,
        ]);
    }

    public function test_sync_model_number_assignments_removes_missing_attributes_and_overrides(): void
    {
        $category = Category::factory()->create();
        $first = $this->makeAttributeDefinition($category, ['key' => 'first_attr', 'label' => 'First']);
        $second = $this->makeAttributeDefinition($category, ['key' => 'second_attr', 'label' => 'Second']);
        $model = AssetModel::factory()->create(['category_id' => $category->id]);
        $modelNumber = $model->ensurePrimaryModelNumber();

        $this->assignAttribute($modelNumber, $first, ['display_order' => 0]);
        $this->assignAttribute($modelNumber, $second, ['display_order' => 1]);

        $asset = Asset::factory()->create([
            'model_id' => $model->id,
            'model_number_id' => $modelNumber->id,
        ]);

        AssetAttributeOverride::create([
            'asset_id' => $asset->id,
            'attribute_definition_id' => $second->id,
            'value' => 'override',
            'raw_value' => 'override',
        ]);

        /** @var ModelAttributeManager $manager */
        $manager = app(ModelAttributeManager::class);

        $manager->syncModelNumberAssignments($modelNumber, [$first->id]);

        $this->assertDatabaseMissing('model_number_attributes', [
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $second->id,
        ]);

        $this->assertDatabaseMissing('asset_attribute_overrides', [
            'asset_id' => $asset->id,
            'attribute_definition_id' => $second->id,
        ]);
    }
}

