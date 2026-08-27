<?php

namespace Tests\Unit\Models;

use App\Models\Asset;
use App\Models\AssetExpectedComponentState;
use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionSubcomponentTemplate;
use App\Models\ComponentInstance;
use App\Models\ModelNumberAttribute;
use App\Models\ModelNumberComponentTemplate;
use App\Models\TestType;
use App\Models\User;
use App\Services\ComponentLifecycleService;
use App\Services\Components\ComponentExpectedSubcomponentService;
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
            'allow_custom_values' => false,
            'allow_asset_override' => false,
        ]);
        $definition->categories()->sync([$category->id]);

        $model = AssetModel::factory()->create([
            'category_id' => $category->id,
        ]);

        $modelNumber = $model->ensurePrimaryModelNumber();

        ModelNumberAttribute::create([
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $definition->id,
            'value' => '95',
        ]);

        $testType = TestType::factory()->create([
            'attribute_definition_id' => $definition->id,
            'slug' => 'battery-health',
        ]);

        $asset = Asset::factory()->create([
            'model_id' => $model->id,
            'model_number_id' => $modelNumber->id,
        ]);

        $types = TestType::forAsset($asset)->get();

        $this->assertCount(1, $types);
        $this->assertSame($testType->id, $types->first()->id);
    }

    public function test_returns_empty_collection_when_no_attributes_require_tests(): void
    {
        $category = Category::factory()->create();
        $model = AssetModel::factory()->create(['category_id' => $category->id]);
        $modelNumber = $model->ensurePrimaryModelNumber();
        $asset = Asset::factory()->create([
            'model_id' => $model->id,
            'model_number_id' => $modelNumber->id,
        ]);

        TestType::factory()->create();

        $this->assertCount(0, TestType::forAsset($asset)->get());
    }

    public function test_returns_component_category_items_for_expected_components(): void
    {
        $assetCategory = Category::factory()->assetLaptopCategory()->create();
        $componentCategory = Category::factory()->forComponents()->create(['name' => 'Ports']);
        $definition = ComponentDefinition::factory()->create([
            'category_id' => $componentCategory->id,
            'name' => 'HDMI Port - 1.4b',
        ]);
        $model = AssetModel::factory()->create(['category_id' => $assetCategory->id]);
        $modelNumber = $model->ensurePrimaryModelNumber();
        ModelNumberComponentTemplate::factory()->create([
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $definition->id,
        ]);
        $asset = Asset::factory()->create([
            'model_id' => $model->id,
            'model_number_id' => $modelNumber->id,
        ]);

        $testType = TestType::factory()->create(['slug' => 'port-check']);
        $testType->componentCategories()->sync([$componentCategory->id]);

        $types = TestType::forAsset($asset)->get();

        $this->assertCount(1, $types);
        $this->assertSame($testType->id, $types->first()->id);
    }

    public function test_fully_removed_expected_component_no_longer_applies(): void
    {
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $definition = ComponentDefinition::factory()->create();
        $template = ModelNumberComponentTemplate::factory()->create([
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $definition->id,
            'expected_qty' => 1,
        ]);
        $asset = Asset::factory()->create([
            'model_id' => $model->id,
            'model_number_id' => $modelNumber->id,
        ]);
        AssetExpectedComponentState::create([
            'asset_id' => $asset->id,
            'model_number_component_template_id' => $template->id,
            'removed_qty' => 1,
        ]);
        $testType = TestType::factory()->create(['slug' => 'removed-component']);
        $testType->componentDefinitions()->sync([$definition->id]);

        $this->assertFalse(TestType::forAsset($asset)->get()->contains('id', $testType->id));
    }

    public function test_partially_removed_expected_component_still_applies(): void
    {
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $definition = ComponentDefinition::factory()->create();
        $template = ModelNumberComponentTemplate::factory()->create([
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $definition->id,
            'expected_qty' => 2,
        ]);
        $asset = Asset::factory()->create([
            'model_id' => $model->id,
            'model_number_id' => $modelNumber->id,
        ]);
        AssetExpectedComponentState::create([
            'asset_id' => $asset->id,
            'model_number_component_template_id' => $template->id,
            'removed_qty' => 1,
        ]);
        $testType = TestType::factory()->create(['slug' => 'remaining-component']);
        $testType->componentDefinitions()->sync([$definition->id]);

        $this->assertTrue(TestType::forAsset($asset)->get()->contains('id', $testType->id));
    }

    public function test_returns_component_category_items_for_expected_subcomponents(): void
    {
        $assetCategory = Category::factory()->assetLaptopCategory()->create();
        $boardCategory = Category::factory()->forComponents()->create(['name' => 'Logic Board']);
        $portCategory = Category::factory()->forComponents()->create(['name' => 'Ports']);
        $boardDefinition = ComponentDefinition::factory()->create([
            'category_id' => $boardCategory->id,
            'name' => 'Motherboard',
        ]);
        $portDefinition = ComponentDefinition::factory()->create([
            'category_id' => $portCategory->id,
            'name' => 'USB-C Port',
        ]);
        ComponentDefinitionSubcomponentTemplate::create([
            'parent_component_definition_id' => $boardDefinition->id,
            'child_component_definition_id' => $portDefinition->id,
            'expected_name' => 'USB-C Port',
            'expected_qty' => 1,
            'is_required' => true,
            'sort_order' => 0,
        ]);
        $model = AssetModel::factory()->create(['category_id' => $assetCategory->id]);
        $modelNumber = $model->ensurePrimaryModelNumber();
        ModelNumberComponentTemplate::factory()->create([
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $boardDefinition->id,
        ]);
        $asset = Asset::factory()->create([
            'model_id' => $model->id,
            'model_number_id' => $modelNumber->id,
        ]);

        $testType = TestType::factory()->create(['slug' => 'port-check']);
        $testType->componentCategories()->sync([$portCategory->id]);

        $types = TestType::forAsset($asset)->get();

        $this->assertCount(1, $types);
        $this->assertSame($testType->id, $types->first()->id);
    }

    public function test_returns_component_definition_items_for_attached_components(): void
    {
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $asset = Asset::factory()->create([
            'model_id' => $model->id,
            'model_number_id' => $modelNumber->id,
        ]);
        $definition = ComponentDefinition::factory()->create(['name' => 'Webcam']);
        ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $definition->id,
        ]);

        $testType = TestType::factory()->create(['slug' => 'webcam']);
        $testType->componentDefinitions()->sync([$definition->id]);

        $types = TestType::forAsset($asset)->get();

        $this->assertCount(1, $types);
        $this->assertSame($testType->id, $types->first()->id);
    }

    public function test_detached_tracked_component_no_longer_applies(): void
    {
        $asset = Asset::factory()->create();
        $definition = ComponentDefinition::factory()->create(['name' => 'Detachable Camera']);
        $component = ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $definition->id,
        ]);
        $testType = TestType::factory()->create(['slug' => 'detachable-camera']);
        $testType->componentDefinitions()->sync([$definition->id]);

        $this->assertTrue(TestType::forAsset($asset)->get()->contains('id', $testType->id));

        app(ComponentLifecycleService::class)->removeToTray(
            $component,
            User::factory()->superuser()->create(),
            ['note' => 'Detached during applicability test.']
        );

        $this->assertFalse(TestType::forAsset($asset->fresh())->get()->contains('id', $testType->id));
    }

    public function test_removed_expected_subcomponent_no_longer_applies_after_detachment(): void
    {
        $actor = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $parentDefinition = ComponentDefinition::factory()->create(['name' => 'Main Board']);
        $childDefinition = ComponentDefinition::factory()->create(['name' => 'Port Board']);
        $childTemplate = ComponentDefinitionSubcomponentTemplate::factory()->create([
            'parent_component_definition_id' => $parentDefinition->id,
            'child_component_definition_id' => $childDefinition->id,
            'expected_qty' => 1,
        ]);
        $parentTemplate = ModelNumberComponentTemplate::factory()->create([
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $parentDefinition->id,
            'expected_qty' => 1,
        ]);
        $asset = Asset::factory()->create([
            'model_id' => $model->id,
            'model_number_id' => $modelNumber->id,
        ]);
        AssetExpectedComponentState::create([
            'asset_id' => $asset->id,
            'model_number_component_template_id' => $parentTemplate->id,
            'removed_qty' => 1,
        ]);
        $parent = ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $parentDefinition->id,
        ]);
        $testType = TestType::factory()->create(['slug' => 'port-board']);
        $testType->componentDefinitions()->sync([$childDefinition->id]);

        $this->assertTrue(TestType::forAsset($asset)->get()->contains('id', $testType->id));

        $child = app(ComponentExpectedSubcomponentService::class)->materializeAttachedChild(
            $parent,
            $childTemplate,
            $actor,
            ['condition_warning_confirmed' => true]
        );

        $this->assertTrue(TestType::forAsset($asset->fresh())->get()->contains('id', $testType->id));

        app(ComponentLifecycleService::class)->removeToTray(
            $child,
            $actor,
            ['note' => 'Removed expected child.']
        );

        $this->assertFalse(TestType::forAsset($asset->fresh())->get()->contains('id', $testType->id));
    }

    public function test_always_apply_items_are_explicitly_global(): void
    {
        $category = Category::factory()->create();
        $model = AssetModel::factory()->create(['category_id' => $category->id]);
        $modelNumber = $model->ensurePrimaryModelNumber();
        $asset = Asset::factory()->create([
            'model_id' => $model->id,
            'model_number_id' => $modelNumber->id,
        ]);

        $blankItem = TestType::factory()->create(['slug' => 'blank-item']);
        $alwaysItem = TestType::factory()->create([
            'slug' => 'always-item',
            'applies_to_all' => true,
        ]);

        $types = TestType::forAsset($asset)->get();

        $this->assertCount(1, $types);
        $this->assertSame($alwaysItem->id, $types->first()->id);
        $this->assertFalse($types->contains('id', $blankItem->id));
    }
}
