<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\ModelNumberAttribute;
use App\Models\User;
use App\Services\ModelAttributes\ModelAttributeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetSpecificationOverrideTest extends TestCase
{
    use RefreshDatabase;

    private function createAttribute(Category $category, array $overrides = []): AttributeDefinition
    {
        $definition = AttributeDefinition::create(array_merge([
            'key' => 'attr_'.uniqid(),
            'label' => 'Test Attribute',
            'datatype' => AttributeDefinition::DATATYPE_BOOL,
            'required_for_category' => true,
            'needs_test' => true,
            'allow_custom_values' => false,
            'allow_asset_override' => true,
        ], $overrides));

        $definition->categories()->sync([$category->id]);

        return $definition;
    }

    private function makeAssetWithSpec(): array
    {
        $category = Category::factory()->create();
        $definition = $this->createAttribute($category);
        $model = AssetModel::factory()->create(['category_id' => $category->id]);
        $modelNumber = $model->ensurePrimaryModelNumber();

        ModelNumberAttribute::create([
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $definition->id,
            'value' => '1',
        ]);

        $asset = Asset::factory()->create([
            'model_id' => $model->id,
            'model_number_id' => $modelNumber->id,
        ]);

        return [$asset, $definition, $modelNumber];
    }

    public function test_asset_update_invokes_manager_with_override_payload(): void
    {
        [$asset, $definition, $modelNumber] = $this->makeAssetWithSpec();

        $user = User::factory()->superuser()->create();
        $this->actingAs($user);

        app(\App\Services\ModelAttributes\ModelAttributeManager::class)
            ->saveAssetOverrides($asset, [
                $definition->id => '0',
            ]);

        $this->assertDatabaseHas('asset_attribute_overrides', [
            'asset_id' => $asset->id,
            'attribute_definition_id' => $definition->id,
            'value' => '0',
        ]);
    }

    public function test_asset_update_ignores_override_payload_when_not_allowed(): void
    {
        [$asset, $definition, $modelNumber] = $this->makeAssetWithSpec();
        $definition->update(['allow_asset_override' => false]);

        $user = User::factory()->superuser()->create();
        $this->actingAs($user);

        try {
            app(\App\Services\ModelAttributes\ModelAttributeManager::class)
                ->saveAssetOverrides($asset, [
                    $definition->id => '1',
                ]);
            $this->fail('Expected validation exception was not thrown.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey($definition->key, $exception->errors());
        }
    }
}
