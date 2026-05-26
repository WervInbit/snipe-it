<?php

namespace Tests\Feature\Models;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\ComponentDefinition;
use App\Models\ModelNumberAttribute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelSpecificationUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function makeDefinitionForModel(AssetModel $model, array $overrides = []): AttributeDefinition
    {
        $definition = AttributeDefinition::create(array_merge([
            'key' => 'spec_'.uniqid(),
            'label' => 'Spec '.uniqid(),
            'datatype' => AttributeDefinition::DATATYPE_TEXT,
            'required_for_category' => false,
            'allow_custom_values' => true,
            'allow_asset_override' => true,
        ], $overrides));

        $definition->categories()->sync([$model->category_id]);

        return $definition;
    }

    public function test_specification_page_surfaces_field_errors_with_error_navigator(): void
    {
        $user = User::factory()->superuser()->create();
        $category = Category::factory()->forAssets()->create();
        $model = AssetModel::factory()->create(['category_id' => $category->id]);
        $modelNumber = $model->ensurePrimaryModelNumber();
        $definition = $this->makeDefinitionForModel($model, [
            'datatype' => AttributeDefinition::DATATYPE_INT,
            'label' => 'Battery Cycles',
        ]);

        ModelNumberAttribute::create([
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $definition->id,
            'display_order' => 0,
        ]);

        $response = $this->actingAs($user)
            ->from(route('models.spec.edit', ['model' => $model, 'model_number_id' => $modelNumber->id]))
            ->put(route('models.spec.update', $model), [
                'model_number_id' => $modelNumber->id,
                'attribute_order' => [$definition->id],
                'attributes' => [
                    $definition->id => 'not-an-int',
                ],
            ]);

        $response->assertSessionHasErrors(['attributes.'.$definition->id]);

        $this->followRedirects($response)
            ->assertSee('data-testid="model-spec-error-navigator"', false)
            ->assertSee('data-testid="model-spec-error-link"', false)
            ->assertSee('Battery Cycles')
            ->assertSee('data-testid="model-attributes-builder"', false)
            ->assertSee('data-invalid-attribute-ids="'.$definition->id.'"', false)
            ->assertSee('selected-attribute-item--error', false);
    }

    public function test_required_missing_specs_now_emit_per_field_errors(): void
    {
        $user = User::factory()->superuser()->create();
        $category = Category::factory()->forAssets()->create();
        $model = AssetModel::factory()->create(['category_id' => $category->id]);
        $modelNumber = $model->ensurePrimaryModelNumber();
        $definition = $this->makeDefinitionForModel($model, [
            'datatype' => AttributeDefinition::DATATYPE_TEXT,
            'required_for_category' => true,
            'label' => 'Storage Condition',
        ]);

        ModelNumberAttribute::create([
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $definition->id,
            'display_order' => 0,
            'value' => null,
            'raw_value' => null,
            'attribute_option_id' => null,
        ]);

        $this->actingAs($user)
            ->from(route('models.spec.edit', ['model' => $model, 'model_number_id' => $modelNumber->id]))
            ->put(route('models.spec.update', $model), [
                'model_number_id' => $modelNumber->id,
                'attribute_order' => [$definition->id],
                'attributes' => [],
            ])
            ->assertSessionHasErrors([
                'attributes',
                'attributes.'.$definition->id,
            ]);
    }

    public function test_specification_update_rejects_subcomponent_only_expected_components(): void
    {
        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $definition = ComponentDefinition::factory()->create([
            'is_active' => true,
            'placement_mode' => ComponentDefinition::PLACEMENT_SUBCOMPONENT_ONLY,
        ]);

        $this->actingAs($user)
            ->from(route('models.spec.edit', ['model' => $model, 'model_number_id' => $modelNumber->id]))
            ->put(route('models.spec.update', $model), [
                'model_number_id' => $modelNumber->id,
                'component_templates' => [
                    [
                        'component_definition_id' => $definition->id,
                        'expected_qty' => 1,
                    ],
                ],
            ])
            ->assertSessionHasErrors(['component_templates.0.component_definition_id']);

        $this->assertDatabaseMissing('model_number_component_templates', [
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $definition->id,
        ]);
    }
}
