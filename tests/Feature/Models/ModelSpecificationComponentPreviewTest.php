<?php

namespace Tests\Feature\Models;

use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionAttribute;
use App\Models\ComponentDefinitionSubcomponentTemplate;
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
            ->assertSee('js-component-template-definition-select', false)
            ->assertSee('select2', false)
            ->assertDontSee('name="component_templates[0][expected_name]"', false)
            ->assertDontSee('name="component_templates[0][is_required]"', false)
            ->assertDontSee('name="component_templates[0][slot_name]"', false)
            ->assertDontSee('name="component_templates[0][notes]"', false);
    }

    public function test_specification_page_shows_expected_component_child_preview_and_overlap_warning(): void
    {
        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $usbPorts = AttributeDefinition::create([
            'key' => 'usb_port_count',
            'label' => 'USB Port Count',
            'datatype' => AttributeDefinition::DATATYPE_INT,
            'allow_asset_override' => true,
        ]);
        $parentDefinition = ComponentDefinition::factory()->create([
            'name' => 'Motherboard Assembly',
        ]);
        $childDefinition = ComponentDefinition::factory()->create([
            'name' => 'USB-C Port Board',
            'part_code' => 'USB-C-PORT',
            'is_active' => true,
        ]);
        ComponentDefinitionAttribute::create([
            'component_definition_id' => $parentDefinition->id,
            'attribute_definition_id' => $usbPorts->id,
            'value' => '4',
            'raw_value' => '4',
            'resolves_to_spec' => true,
        ]);
        ComponentDefinitionAttribute::create([
            'component_definition_id' => $childDefinition->id,
            'attribute_definition_id' => $usbPorts->id,
            'value' => '1',
            'raw_value' => '1',
            'resolves_to_spec' => true,
        ]);
        ComponentDefinitionSubcomponentTemplate::factory()->create([
            'parent_component_definition_id' => $parentDefinition->id,
            'child_component_definition_id' => $childDefinition->id,
            'expected_name' => 'Left USB-C Port Board',
            'expected_qty' => 2,
        ]);
        ModelNumberComponentTemplate::create([
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $parentDefinition->id,
            'expected_name' => 'Motherboard Assembly',
            'expected_qty' => 1,
            'is_required' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('models.numbers.spec.edit', [$model, $modelNumber]))
            ->assertOk()
            ->assertSee('data-testid="model-number-expected-child-preview"', false)
            ->assertSeeText('Expected child structure')
            ->assertSeeText('Left USB-C Port Board')
            ->assertSeeText('USB-C-PORT')
            ->assertSeeText('x2')
            ->assertSee(route('settings.component_definitions.edit', $parentDefinition), false)
            ->assertSee(route('settings.component_definitions.edit', $childDefinition), false)
            ->assertSee('data-testid="component-definition-hierarchy-overlap-warning"', false)
            ->assertSeeText('Hierarchy overlap warning')
            ->assertSeeText('USB Port Count')
            ->assertDontSee('name="expected_subcomponents[0][expected_name]"', false);
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

    public function test_specification_page_warns_when_manual_value_conflicts_with_component_spec(): void
    {
        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $storageType = AttributeDefinition::create([
            'key' => 'storage_type',
            'label' => 'Opslagtype',
            'datatype' => AttributeDefinition::DATATYPE_ENUM,
            'allow_custom_values' => false,
        ]);
        $nvmeOption = $storageType->options()->create([
            'value' => 'nvme',
            'label' => 'NVMe-SSD',
            'active' => true,
            'sort_order' => 0,
        ]);
        $ssdOption = $storageType->options()->create([
            'value' => 'ssd',
            'label' => 'SATA-SSD',
            'active' => true,
            'sort_order' => 1,
        ]);
        $componentDefinition = ComponentDefinition::factory()->create([
            'name' => 'Storage 128GB SATA SSD',
        ]);
        ComponentDefinitionAttribute::create([
            'component_definition_id' => $componentDefinition->id,
            'attribute_definition_id' => $storageType->id,
            'attribute_option_id' => $ssdOption->id,
            'value' => 'ssd',
            'raw_value' => 'ssd',
            'sort_order' => 0,
            'resolves_to_spec' => true,
        ]);
        ModelNumberComponentTemplate::create([
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $componentDefinition->id,
            'expected_name' => 'Storage Device',
            'expected_qty' => 1,
            'is_required' => true,
            'sort_order' => 0,
        ]);
        ModelNumberAttribute::create([
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $storageType->id,
            'attribute_option_id' => $nvmeOption->id,
            'value' => 'nvme',
            'raw_value' => 'nvme',
            'display_order' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('models.numbers.spec.edit', [$model, $modelNumber]))
            ->assertOk()
            ->assertSee('data-testid="model-spec-component-conflict-warning"', false)
            ->assertSeeText('Component specification conflict')
            ->assertSeeText('Opslagtype')
            ->assertSeeText('NVMe-SSD')
            ->assertSeeText('SATA-SSD')
            ->assertSeeText('Component value is being used.');
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
