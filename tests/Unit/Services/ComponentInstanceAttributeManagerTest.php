<?php

namespace Tests\Unit\Services;

use App\Models\AttributeDefinition;
use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionAttribute;
use App\Models\ComponentInstance;
use App\Services\ModelAttributes\ComponentInstanceAttributeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ComponentInstanceAttributeManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_persists_normalized_instance_attributes(): void
    {
        $component = ComponentInstance::factory()->create([
            'component_definition_id' => null,
            'display_name' => 'Custom Memory Module',
        ]);
        $memoryType = AttributeDefinition::create([
            'key' => 'memory_type',
            'label' => 'Memory Type',
            'datatype' => AttributeDefinition::DATATYPE_ENUM,
            'allow_custom_values' => false,
        ]);
        $memoryTypeOption = $memoryType->options()->create([
            'value' => 'DDR5',
            'label' => 'DDR5',
            'active' => true,
            'sort_order' => 0,
        ]);
        $capacity = AttributeDefinition::create([
            'key' => 'ram_capacity_gb',
            'label' => 'RAM Capacity',
            'datatype' => AttributeDefinition::DATATYPE_INT,
        ]);
        $voltage = AttributeDefinition::create([
            'key' => 'module_voltage',
            'label' => 'Module Voltage',
            'datatype' => AttributeDefinition::DATATYPE_DECIMAL,
            'constraints' => ['min' => 1, 'max' => 2, 'step' => 0.1],
        ]);
        $ecc = AttributeDefinition::create([
            'key' => 'ecc_supported',
            'label' => 'ECC Supported',
            'datatype' => AttributeDefinition::DATATYPE_BOOL,
        ]);

        app(ComponentInstanceAttributeManager::class)->sync($component, [
            [
                'attribute_definition_id' => $memoryType->id,
                'value' => 'DDR5',
            ],
            [
                'attribute_key' => 'ram_capacity_gb',
                'value' => '32',
                'resolves_to_spec' => true,
            ],
            [
                'attribute_definition_id' => $voltage->id,
                'value' => '1.20',
            ],
            [
                'attribute_definition_id' => $ecc->id,
                'value' => 'yes',
            ],
        ]);

        $this->assertDatabaseHas('component_instance_attributes', [
            'component_instance_id' => $component->id,
            'attribute_definition_id' => $memoryType->id,
            'attribute_option_id' => $memoryTypeOption->id,
            'value' => 'DDR5',
            'raw_value' => 'DDR5',
        ]);
        $this->assertDatabaseHas('component_instance_attributes', [
            'component_instance_id' => $component->id,
            'attribute_definition_id' => $capacity->id,
            'value' => '32',
            'raw_value' => '32',
            'resolves_to_spec' => 1,
        ]);
        $this->assertDatabaseHas('component_instance_attributes', [
            'component_instance_id' => $component->id,
            'attribute_definition_id' => $voltage->id,
            'value' => '1.2',
            'raw_value' => '1.20',
        ]);
        $this->assertDatabaseHas('component_instance_attributes', [
            'component_instance_id' => $component->id,
            'attribute_definition_id' => $ecc->id,
            'value' => '1',
            'raw_value' => 'yes',
        ]);
    }

    public function test_sync_inherits_definition_spec_resolution_for_overrides(): void
    {
        $capacity = AttributeDefinition::create([
            'key' => 'ram_capacity_gb',
            'label' => 'RAM Capacity',
            'datatype' => AttributeDefinition::DATATYPE_INT,
        ]);
        $componentDefinition = ComponentDefinition::factory()->create();
        ComponentDefinitionAttribute::create([
            'component_definition_id' => $componentDefinition->id,
            'attribute_definition_id' => $capacity->id,
            'value' => '8',
            'raw_value' => '8',
            'resolves_to_spec' => true,
        ]);
        $component = ComponentInstance::factory()->create([
            'component_definition_id' => $componentDefinition->id,
        ]);

        app(ComponentInstanceAttributeManager::class)->sync($component, [
            [
                'attribute_definition_id' => $capacity->id,
                'value' => '16',
            ],
        ]);

        $this->assertDatabaseHas('component_instance_attributes', [
            'component_instance_id' => $component->id,
            'attribute_definition_id' => $capacity->id,
            'value' => '16',
            'resolves_to_spec' => 1,
        ]);
    }

    public function test_sync_rejects_values_that_fail_attribute_validation(): void
    {
        $this->expectException(ValidationException::class);

        $component = ComponentInstance::factory()->create();
        $capacity = AttributeDefinition::create([
            'key' => 'ram_capacity_gb',
            'label' => 'RAM Capacity',
            'datatype' => AttributeDefinition::DATATYPE_INT,
        ]);

        app(ComponentInstanceAttributeManager::class)->sync($component, [
            [
                'attribute_definition_id' => $capacity->id,
                'value' => '8.5',
            ],
        ]);
    }

    public function test_sync_rejects_duplicate_attributes(): void
    {
        $this->expectException(ValidationException::class);

        $component = ComponentInstance::factory()->create();
        $capacity = AttributeDefinition::create([
            'key' => 'ram_capacity_gb',
            'label' => 'RAM Capacity',
            'datatype' => AttributeDefinition::DATATYPE_INT,
        ]);

        app(ComponentInstanceAttributeManager::class)->sync($component, [
            [
                'attribute_definition_id' => $capacity->id,
                'value' => '8',
            ],
            [
                'attribute_definition_id' => $capacity->id,
                'value' => '16',
            ],
        ]);
    }

    public function test_sync_replaces_existing_rows(): void
    {
        $component = ComponentInstance::factory()->create();
        $capacity = AttributeDefinition::create([
            'key' => 'ram_capacity_gb',
            'label' => 'RAM Capacity',
            'datatype' => AttributeDefinition::DATATYPE_INT,
        ]);
        $speed = AttributeDefinition::create([
            'key' => 'ram_speed_mhz',
            'label' => 'RAM Speed',
            'datatype' => AttributeDefinition::DATATYPE_INT,
        ]);
        $manager = app(ComponentInstanceAttributeManager::class);

        $manager->sync($component, [
            ['attribute_definition_id' => $capacity->id, 'value' => '16'],
            ['attribute_definition_id' => $speed->id, 'value' => '3200'],
        ]);
        $manager->sync($component, [
            ['attribute_definition_id' => $speed->id, 'value' => '4800'],
        ]);

        $this->assertDatabaseMissing('component_instance_attributes', [
            'component_instance_id' => $component->id,
            'attribute_definition_id' => $capacity->id,
        ]);
        $this->assertDatabaseHas('component_instance_attributes', [
            'component_instance_id' => $component->id,
            'attribute_definition_id' => $speed->id,
            'value' => '4800',
        ]);
    }
}
