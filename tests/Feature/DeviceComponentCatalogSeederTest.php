<?php

namespace Tests\Feature;

use App\Models\AttributeDefinition;
use App\Models\AssetModel;
use App\Models\ComponentDefinition;
use App\Models\Group;
use App\Models\ModelNumber;
use App\Models\Setting;
use App\Models\Statuslabel;
use App\Models\Supplier;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DeviceAttributeSeeder;
use Database\Seeders\DeviceComponentCatalogSeeder;
use Database\Seeders\DevicePresetSeeder;
use Database\Seeders\ProductionFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeviceComponentCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_database_seeder_creates_production_catalog_without_demo_users_or_companies(): void
    {
        Setting::query()->delete();

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('settings', [
            'site_name' => 'Snipe-IT',
        ]);
        $this->assertSame(0, DB::table('users')->count());
        $this->assertSame(0, DB::table('companies')->count());
        $this->assertGreaterThan(0, AttributeDefinition::query()->count());
        $this->assertGreaterThan(0, AssetModel::query()->count());
        $this->assertGreaterThan(0, ComponentDefinition::query()->count());
        $this->assertGreaterThan(0, Group::query()->count());
        $this->assertGreaterThan(0, Statuslabel::query()->count());
        $this->assertDatabaseMissing('users', [
            'username' => 'demo_user',
        ]);
        $this->assertDatabaseMissing('users', [
            'username' => 'demo_admin',
        ]);
        $this->assertDatabaseMissing('suppliers', [
            'name' => 'TechCycle Partners',
        ]);
        $this->assertDatabaseMissing('suppliers', [
            'name' => 'Renewed Supply Co.',
        ]);
        $this->assertDatabaseHas('status_labels', [
            'name' => 'Stand-by',
            'pending' => 1,
            'default_label' => 1,
        ]);
        $this->assertDatabaseHas('status_labels', [
            'name' => 'Ready for Sale',
            'deployable' => 1,
        ]);
        $this->assertDatabaseHas('status_labels', [
            'name' => 'Returned / RMA',
            'pending' => 1,
        ]);

        $supervisorPermissions = json_decode(Group::query()->where('name', 'Supervisor')->firstOrFail()->permissions, true);
        $adminPermissions = json_decode(Group::query()->where('name', 'Admin')->firstOrFail()->permissions, true);

        $this->assertSame(1, $supervisorPermissions['assets.sale_transition'] ?? null);
        $this->assertArrayNotHasKey('supervisor', $supervisorPermissions);
        $this->assertSame(1, $adminPermissions['admin'] ?? null);
    }

    public function test_production_foundation_seeder_is_idempotent_for_foundation_rows(): void
    {
        Setting::query()->delete();

        $this->seed(ProductionFoundationSeeder::class);
        $counts = $this->productionFoundationCounts();

        $this->seed(ProductionFoundationSeeder::class);

        $this->assertSame($counts, $this->productionFoundationCounts());
        $this->assertSame(0, DB::table('users')->count());
        $this->assertSame(0, DB::table('companies')->count());
        $this->assertSame(0, Supplier::query()->whereIn('name', [
            'TechCycle Partners',
            'Renewed Supply Co.',
        ])->count());
    }

    public function test_component_catalog_renames_legacy_webcam_and_wireless_component_definitions(): void
    {
        $this->seed(DeviceAttributeSeeder::class);

        $legacyWebcam = ComponentDefinition::factory()->create(['name' => 'Webcam Module']);
        $legacyWireless = ComponentDefinition::factory()->create(['name' => 'Wireless Module']);

        $this->seed(DeviceComponentCatalogSeeder::class);

        $this->assertDatabaseHas('component_definitions', [
            'id' => $legacyWebcam->id,
            'name' => 'Webcam',
        ]);
        $this->assertDatabaseHas('component_definitions', [
            'id' => $legacyWireless->id,
            'name' => 'Wireless',
        ]);
        $this->assertDatabaseMissing('component_definitions', [
            'name' => 'Webcam Module',
        ]);
        $this->assertDatabaseMissing('component_definitions', [
            'name' => 'Wireless Module',
        ]);
    }

    public function test_device_attribute_seeder_places_units_on_unit_column_not_labels(): void
    {
        $this->seed(DeviceAttributeSeeder::class);

        $this->assertDatabaseHas('attribute_definitions', [
            'key' => 'ram_size_gb',
            'label' => 'Werkgeheugen',
            'unit' => 'GB',
        ]);
        $this->assertDatabaseHas('attribute_definitions', [
            'key' => 'storage_capacity_gb',
            'label' => 'Opslagcapaciteit',
            'unit' => 'GB',
        ]);
        $this->assertDatabaseHas('attribute_definitions', [
            'key' => 'display_size_inches',
            'label' => 'Schermgrootte',
            'unit' => 'in',
        ]);
        $this->assertDatabaseHas('attribute_definitions', [
            'key' => 'display_refresh_rate_hz',
            'label' => 'Verversingssnelheid',
            'unit' => 'Hz',
        ]);
        $this->assertDatabaseMissing('attribute_definitions', [
            'key' => 'ram_size_gb',
            'label' => 'Werkgeheugen (GB)',
        ]);
        $this->assertDatabaseMissing('attribute_definitions', [
            'key' => 'storage_capacity_gb',
            'label' => 'Opslagcapaciteit (GB)',
        ]);
    }

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

        $this->assertSame(AttributeDefinition::COMPONENT_SPEC_DISPLAY_COMPONENT_LABELS, $portConnectorType->component_spec_display_mode);

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
            'include_in_component_label' => true,
        ]);
        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $audioPort->id,
            'attribute_definition_id' => $audioPortRole->id,
            'value' => 'headset_combo',
            'resolves_to_spec' => false,
            'include_in_component_label' => true,
        ]);
        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $audioPort->id,
            'attribute_definition_id' => $audioJackStandard->id,
            'value' => 'trrs_ctia',
            'resolves_to_spec' => false,
            'include_in_component_label' => false,
        ]);
        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $ethernetPort->id,
            'attribute_definition_id' => $portConnectorType->id,
            'value' => 'rj45',
            'resolves_to_spec' => true,
            'include_in_component_label' => true,
        ]);
        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $ethernetPort->id,
            'attribute_definition_id' => $ethernetSpeedMax->id,
            'value' => '1gbe',
            'resolves_to_spec' => false,
            'include_in_component_label' => true,
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

    public function test_catalog_seeds_generic_fallback_definitions_without_assigning_them_to_models(): void
    {
        $this->seed(DeviceAttributeSeeder::class);
        $this->seed(DevicePresetSeeder::class);
        $this->seed(DeviceComponentCatalogSeeder::class);

        $portConnectorType = AttributeDefinition::query()->where('key', 'port_connector_type')->firstOrFail();
        $storageType = AttributeDefinition::query()->where('key', 'storage_type')->firstOrFail();
        $genericNames = [
            'USB-A Port - Generic',
            'USB-C Port - Generic',
            'HDMI Port',
            'DisplayPort',
            'eSATA Port',
            'RAM - Generic',
            'SSD - Generic',
            'HDD - Generic',
            'Battery - Generic',
            'Camera - Generic',
            'Keyboard - Generic',
            'Wireless - Generic',
            'Bluetooth - Generic',
        ];

        $genericDefinitions = ComponentDefinition::query()
            ->whereIn('name', $genericNames)
            ->get()
            ->keyBy('name');

        foreach ($genericNames as $name) {
            $this->assertTrue($genericDefinitions->has($name), "Missing generic component definition: {$name}");
            $this->assertDatabaseMissing('model_number_component_templates', [
                'component_definition_id' => $genericDefinitions->get($name)->id,
            ]);
        }

        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $genericDefinitions->get('USB-A Port - Generic')->id,
            'attribute_definition_id' => $portConnectorType->id,
            'value' => 'usb_a',
            'resolves_to_spec' => true,
            'include_in_component_label' => true,
        ]);
        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $genericDefinitions->get('USB-C Port - Generic')->id,
            'attribute_definition_id' => $portConnectorType->id,
            'value' => 'usb_c',
            'resolves_to_spec' => true,
            'include_in_component_label' => true,
        ]);
        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $genericDefinitions->get('HDMI Port')->id,
            'attribute_definition_id' => $portConnectorType->id,
            'value' => 'hdmi',
            'resolves_to_spec' => true,
            'include_in_component_label' => true,
        ]);
        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $genericDefinitions->get('DisplayPort')->id,
            'attribute_definition_id' => $portConnectorType->id,
            'value' => 'displayport',
            'resolves_to_spec' => true,
            'include_in_component_label' => true,
        ]);
        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $genericDefinitions->get('eSATA Port')->id,
            'attribute_definition_id' => $portConnectorType->id,
            'value' => 'esata',
            'resolves_to_spec' => true,
            'include_in_component_label' => true,
        ]);
        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $genericDefinitions->get('SSD - Generic')->id,
            'attribute_definition_id' => $storageType->id,
            'value' => 'ssd',
            'resolves_to_spec' => true,
        ]);
        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $genericDefinitions->get('HDD - Generic')->id,
            'attribute_definition_id' => $storageType->id,
            'value' => 'hdd',
            'resolves_to_spec' => true,
        ]);
    }

    /**
     * @return array<string,int>
     */
    private function productionFoundationCounts(): array
    {
        return [
            'settings' => Setting::query()->count(),
            'users' => DB::table('users')->count(),
            'companies' => DB::table('companies')->count(),
            'suppliers' => Supplier::query()->count(),
            'status_labels' => Statuslabel::query()->count(),
            'permission_groups' => Group::query()->count(),
            'attribute_definitions' => AttributeDefinition::query()->count(),
            'models' => AssetModel::query()->count(),
            'model_numbers' => ModelNumber::query()->count(),
            'component_definitions' => ComponentDefinition::query()->count(),
            'workflow_items' => DB::table('workflow_items')->count(),
            'workflow_profiles' => DB::table('workflow_profiles')->count(),
        ];
    }
}
