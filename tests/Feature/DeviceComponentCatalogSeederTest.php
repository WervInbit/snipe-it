<?php

namespace Tests\Feature;

use App\Models\AttributeDefinition;
use App\Models\ComponentDefinition;
use App\Models\ModelNumber;
use Database\Seeders\DeviceAttributeSeeder;
use Database\Seeders\DeviceComponentCatalogSeeder;
use Database\Seeders\DevicePresetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceComponentCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_seeds_model_specific_logic_board_and_child_components(): void
    {
        $this->seed(DeviceAttributeSeeder::class);
        $this->seed(DevicePresetSeeder::class);
        $this->seed(DeviceComponentCatalogSeeder::class);

        $modelNumber = ModelNumber::query()->where('code', '2E9F8EA#ABH')->firstOrFail();
        $ethernetModelNumber = ModelNumber::query()->where('code', '5TK76EA#ABH')->firstOrFail();
        $board = ComponentDefinition::query()
            ->where('name', 'Motherboard - HP ProBook 450 G8 - i5-1135G7')
            ->firstOrFail();
        $ethernetBoard = ComponentDefinition::query()
            ->where('name', 'Motherboard - HP ProBook 430 G6 - i5-8265U')
            ->firstOrFail();
        $usbA = ComponentDefinition::query()
            ->where('name', 'USB-A Port - USB 3.1 Gen1')
            ->firstOrFail();
        $audioPort = ComponentDefinition::query()
            ->where('name', '3.5mm Port - Headset Combo')
            ->firstOrFail();
        $ethernetPort = ComponentDefinition::query()
            ->where('name', 'RJ-45 Ethernet Port - 1GbE')
            ->firstOrFail();
        $cpu = AttributeDefinition::query()->where('key', 'cpu_model')->firstOrFail();
        $portConnectorType = AttributeDefinition::query()->where('key', 'port_connector_type')->firstOrFail();
        $audioPortRole = AttributeDefinition::query()->where('key', 'audio_port_role')->firstOrFail();
        $audioJackStandard = AttributeDefinition::query()->where('key', 'audio_jack_standard')->firstOrFail();
        $ethernetSpeedMax = AttributeDefinition::query()->where('key', 'ethernet_speed_max')->firstOrFail();

        $this->assertDatabaseHas('model_number_component_templates', [
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $board->id,
            'expected_qty' => 1,
        ]);
        $this->assertDatabaseMissing('model_number_component_templates', [
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $usbA->id,
        ]);
        $this->assertDatabaseHas('component_definition_subcomponent_templates', [
            'parent_component_definition_id' => $board->id,
            'child_component_definition_id' => $usbA->id,
            'expected_qty' => 2,
        ]);
        $this->assertDatabaseHas('component_definition_subcomponent_templates', [
            'parent_component_definition_id' => $board->id,
            'child_component_definition_id' => $audioPort->id,
            'expected_qty' => 1,
        ]);
        $this->assertDatabaseHas('model_number_component_templates', [
            'model_number_id' => $ethernetModelNumber->id,
            'component_definition_id' => $ethernetBoard->id,
            'expected_qty' => 1,
        ]);
        $this->assertDatabaseHas('component_definition_subcomponent_templates', [
            'parent_component_definition_id' => $ethernetBoard->id,
            'child_component_definition_id' => $ethernetPort->id,
            'expected_qty' => 1,
        ]);
        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $board->id,
            'attribute_definition_id' => $cpu->id,
            'value' => 'Intel Core i5-1135G7',
            'resolves_to_spec' => true,
        ]);
        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $audioPort->id,
            'attribute_definition_id' => $portConnectorType->id,
            'value' => 'audio_3_5mm',
            'resolves_to_spec' => true,
        ]);
        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $audioPort->id,
            'attribute_definition_id' => $audioPortRole->id,
            'value' => 'headset_combo',
            'resolves_to_spec' => true,
        ]);
        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $audioPort->id,
            'attribute_definition_id' => $audioJackStandard->id,
            'value' => 'trrs_ctia',
            'resolves_to_spec' => true,
        ]);
        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $ethernetPort->id,
            'attribute_definition_id' => $portConnectorType->id,
            'value' => 'rj45',
            'resolves_to_spec' => true,
        ]);
        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $ethernetPort->id,
            'attribute_definition_id' => $ethernetSpeedMax->id,
            'value' => '1gbe',
            'resolves_to_spec' => true,
        ]);
        $this->assertDatabaseHas('attribute_options', [
            'attribute_definition_id' => $portConnectorType->id,
            'value' => 'esata',
            'label' => 'eSATA',
            'active' => true,
        ]);
        $this->assertDatabaseHas('attribute_options', [
            'attribute_definition_id' => $ethernetSpeedMax->id,
            'value' => '10gbe',
            'label' => '10GbE',
            'active' => true,
        ]);
        $this->assertDatabaseMissing('model_number_attributes', [
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $cpu->id,
        ]);
        $this->assertDatabaseMissing('component_definitions', [
            'name' => '3.5mm Audio Jack',
        ]);
        $this->assertDatabaseMissing('component_definitions', [
            'name' => 'RJ-45 Ethernet Port',
        ]);
    }
}
