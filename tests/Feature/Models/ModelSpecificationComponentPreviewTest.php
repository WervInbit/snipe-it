<?php

namespace Tests\Feature\Models;

use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionAttribute;
use App\Models\ModelNumberAttribute;
use App\Models\ModelNumberComponentTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelSpecificationComponentPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_specification_page_shows_expected_components_without_preview_block(): void
    {
        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $componentDefinition = ComponentDefinition::factory()->create([
            'name' => '8GB DDR4 SODIMM',
        ]);
        ModelNumberComponentTemplate::create([
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $componentDefinition->id,
            'expected_name' => 'Memory Module',
            'slot_name' => 'RAM Slot',
            'expected_qty' => 2,
            'is_required' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('models.numbers.spec.edit', [$model, $modelNumber]))
            ->assertOk()
            ->assertSeeText('Expected Components')
            ->assertDontSeeText('Effective Specification Preview')
            ->assertSeeText('8GB DDR4 SODIMM')
            ->assertSee('data-component-template-drag-handle', false)
            ->assertDontSee('name="component_templates[0][expected_name]"', false)
            ->assertDontSee('name="component_templates[0][is_required]"', false)
            ->assertDontSee('name="component_templates[0][slot_name]"', false)
            ->assertDontSee('name="component_templates[0][notes]"', false);
    }

    public function test_specification_page_does_not_show_preview_or_manual_override_copy(): void
    {
        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $capacity = AttributeDefinition::create([
            'key' => 'ram_capacity_gb',
            'label' => 'RAM Capacity',
            'datatype' => AttributeDefinition::DATATYPE_INT,
            'allow_asset_override' => true,
        ]);
        $componentDefinition = ComponentDefinition::factory()->create([
            'name' => '8GB DDR4 SODIMM',
        ]);
        ComponentDefinitionAttribute::create([
            'component_definition_id' => $componentDefinition->id,
            'attribute_definition_id' => $capacity->id,
            'value' => '8',
            'raw_value' => '8',
            'sort_order' => 0,
            'resolves_to_spec' => true,
        ]);
        ModelNumberComponentTemplate::create([
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $componentDefinition->id,
            'expected_name' => 'Memory Module',
            'expected_qty' => 2,
            'is_required' => true,
            'sort_order' => 0,
        ]);
        ModelNumberAttribute::create([
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $capacity->id,
            'value' => '24',
            'raw_value' => '24',
            'display_order' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('models.numbers.spec.edit', [$model, $modelNumber]))
            ->assertOk()
            ->assertDontSeeText('Effective Specification Preview')
            ->assertDontSeeText('Manual model value currently overrides the derived component total.');
    }

    public function test_specification_page_does_not_seed_blank_expected_component_row(): void
    {
        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();

        $this->actingAs($user)
            ->get(route('models.numbers.spec.edit', [$model, $modelNumber]))
            ->assertOk()
            ->assertSeeText('No expected components added yet.')
            ->assertDontSee('data-component-template-row-index="0"', false);
    }

    public function test_fixed_enum_model_attribute_renders_as_select(): void
    {
        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $memoryType = AttributeDefinition::create([
            'key' => 'memory_type',
            'label' => 'Memory Type',
            'datatype' => AttributeDefinition::DATATYPE_ENUM,
            'allow_custom_values' => false,
            'allow_asset_override' => true,
        ]);
        $memoryType->options()->createMany([
            [
                'value' => 'DDR4',
                'label' => 'DDR4',
                'active' => true,
                'sort_order' => 0,
            ],
            [
                'value' => 'DDR5',
                'label' => 'DDR5',
                'active' => true,
                'sort_order' => 1,
            ],
        ]);
        ModelNumberAttribute::create([
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $memoryType->id,
            'attribute_option_id' => $memoryType->options()->first()->id,
            'value' => 'DDR4',
            'raw_value' => 'DDR4',
            'display_order' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('models.numbers.spec.edit', [$model, $modelNumber]))
            ->assertOk()
            ->assertSee('<select name="attributes[' . $memoryType->id . ']"', false)
            ->assertDontSee('list="attribute_' . $memoryType->id . '_options"', false)
            ->assertSeeText('Use one of the defined options.');
    }
}
