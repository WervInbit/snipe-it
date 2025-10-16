<?php

namespace Tests\Feature\Models;

use App\Models\Asset;
use App\Models\AssetAttributeOverride;
use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\ModelNumberAttribute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelNumberAttributeControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeDefinitionForModel(AssetModel $model, array $overrides = []): AttributeDefinition
    {
        $definition = AttributeDefinition::create(array_merge([
            'key' => 'attr_'.uniqid(),
            'label' => 'Attribute '.uniqid(),
            'datatype' => AttributeDefinition::DATATYPE_TEXT,
            'unit' => null,
            'required_for_category' => false,
            'needs_test' => false,
            'allow_custom_values' => true,
            'allow_asset_override' => true,
        ], $overrides));

        $definition->categories()->sync([$model->category_id]);

        return $definition;
    }

    public function test_superuser_can_assign_attribute_to_model_number(): void
    {
        $user = User::factory()->superuser()->create();
        $category = Category::factory()->create();
        $model = AssetModel::factory()->create(['category_id' => $category->id]);
        $modelNumber = $model->ensurePrimaryModelNumber();
        $definition = $this->makeDefinitionForModel($model);

        $this->actingAs($user)
            ->postJson(
                route('models.numbers.attributes.store', ['model' => $model, 'modelNumber' => $modelNumber]),
                ['attribute_definition_id' => $definition->id]
            )
            ->assertOk()
            ->assertJsonStructure([
                'attribute' => ['id', 'label', 'key'],
                'selected_item',
                'detail',
            ]);

        $this->assertDatabaseHas('model_number_attributes', [
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $definition->id,
            'value' => null,
        ]);
    }

    public function test_assigning_same_attribute_twice_is_idempotent(): void
    {
        $user = User::factory()->superuser()->create();
        $category = Category::factory()->create();
        $model = AssetModel::factory()->create(['category_id' => $category->id]);
        $modelNumber = $model->ensurePrimaryModelNumber();
        $definition = $this->makeDefinitionForModel($model);

        $this->actingAs($user);

        $route = route('models.numbers.attributes.store', ['model' => $model, 'modelNumber' => $modelNumber]);

        $this->postJson($route, ['attribute_definition_id' => $definition->id])->assertOk();
        $this->postJson($route, ['attribute_definition_id' => $definition->id])->assertOk();

        $this->assertSame(1, ModelNumberAttribute::where('model_number_id', $modelNumber->id)
            ->where('attribute_definition_id', $definition->id)
            ->count());
    }

    public function test_superuser_can_remove_attribute_assignment(): void
    {
        $user = User::factory()->superuser()->create();
        $category = Category::factory()->create();
        $model = AssetModel::factory()->create(['category_id' => $category->id]);
        $modelNumber = $model->ensurePrimaryModelNumber();
        $definition = $this->makeDefinitionForModel($model);

        $assignment = ModelNumberAttribute::create([
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $definition->id,
            'display_order' => 0,
        ]);

        $asset = Asset::factory()->create([
            'model_id' => $model->id,
            'model_number_id' => $modelNumber->id,
        ]);

        AssetAttributeOverride::create([
            'asset_id' => $asset->id,
            'attribute_definition_id' => $definition->id,
            'value' => 'override',
            'raw_value' => 'override',
        ]);

        $this->actingAs($user)
            ->deleteJson(route('models.numbers.attributes.destroy', [
                'model' => $model,
                'modelNumber' => $modelNumber,
                'attributeDefinition' => $definition,
            ]))
            ->assertOk()
            ->assertJsonFragment(['status' => 'removed']);

        $this->assertDatabaseMissing('model_number_attributes', [
            'id' => $assignment->id,
        ]);

        $this->assertDatabaseMissing('asset_attribute_overrides', [
            'asset_id' => $asset->id,
            'attribute_definition_id' => $definition->id,
        ]);
    }

    public function test_superuser_can_reorder_attributes(): void
    {
        $user = User::factory()->superuser()->create();
        $category = Category::factory()->create();
        $model = AssetModel::factory()->create(['category_id' => $category->id]);
        $modelNumber = $model->ensurePrimaryModelNumber();
        $firstDefinition = $this->makeDefinitionForModel($model, ['key' => 'first_attr', 'label' => 'First']);
        $secondDefinition = $this->makeDefinitionForModel($model, ['key' => 'second_attr', 'label' => 'Second']);

        $firstAssignment = ModelNumberAttribute::create([
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $firstDefinition->id,
            'display_order' => 0,
        ]);

        $secondAssignment = ModelNumberAttribute::create([
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $secondDefinition->id,
            'display_order' => 1,
        ]);

        $this->actingAs($user)
            ->patchJson(route('models.numbers.attributes.reorder', ['model' => $model, 'modelNumber' => $modelNumber]), [
                'order' => [$secondDefinition->id, $firstDefinition->id],
            ])
            ->assertOk();

        $this->assertDatabaseHas('model_number_attributes', [
            'id' => $firstAssignment->id,
            'display_order' => 1,
        ]);

        $this->assertDatabaseHas('model_number_attributes', [
            'id' => $secondAssignment->id,
            'display_order' => 0,
        ]);
    }
}
