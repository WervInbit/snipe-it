<?php

namespace Tests\Feature;

use App\Models\AttributeDefinition;
use App\Models\AttributeOption;
use App\Models\AssetModel;
use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionAttribute;
use App\Models\ComponentDefinitionSubcomponentTemplate;
use App\Models\Group;
use App\Models\ModelNumber;
use App\Models\ModelNumberAttribute;
use App\Models\ModelNumberComponentTemplate;
use App\Models\Setting;
use App\Models\Statuslabel;
use App\Models\Supplier;
use App\Models\TestType;
use App\Models\WorkflowProfile;
use App\Models\WorkflowProfileItem;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DeviceAttributeSeeder;
use Database\Seeders\DeviceComponentCatalogSeeder;
use Database\Seeders\DevicePresetSeeder;
use Database\Seeders\ProductionFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeviceComponentCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    private const UNVERIFIED_DEMO_MODEL_NUMBERS = [
        'HP-430G3-I3-4-128',
        'MS-SURFPRO4-I5-4-128',
        'MS-SURFPRO5-I5-4-128',
        'IP12-128-BLUE',
        'PIXEL8PRO-256-OBSIDIAN',
    ];

    public function test_default_database_seeder_creates_production_catalog_without_demo_users_or_companies(): void
    {
        Setting::query()->delete();

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('settings', [
            'site_name' => 'Inbit Device Refurbishment',
            'alert_email' => null,
            'logo' => null,
            'brand' => 1,
            'support_footer' => 'off',
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

    public function test_production_foundation_excludes_unverified_demo_model_numbers(): void
    {
        Config::set('demo.allow_disposable_data_seeding', false);

        $this->seed(ProductionFoundationSeeder::class);

        $this->assertDatabaseHas('model_numbers', ['code' => '2E9F8EA#ABH']);
        $this->assertDatabaseHas('model_numbers', ['code' => 'SM-A520F']);

        foreach (self::UNVERIFIED_DEMO_MODEL_NUMBERS as $code) {
            $this->assertDatabaseMissing('model_numbers', ['code' => $code]);
        }
    }

    public function test_local_disposable_opt_in_includes_labeled_demo_model_numbers_and_templates(): void
    {
        Config::set('demo.allow_disposable_data_seeding', true);

        $this->seed(DeviceAttributeSeeder::class);
        $this->seed(DevicePresetSeeder::class);
        $this->seed(DeviceComponentCatalogSeeder::class);

        foreach (self::UNVERIFIED_DEMO_MODEL_NUMBERS as $code) {
            $modelNumber = ModelNumber::query()->where('code', $code)->firstOrFail();

            $this->assertStringStartsWith('DEMO placeholder - ', (string) $modelNumber->label);
            $this->assertTrue($modelNumber->componentTemplates()->exists(), $code);
        }
    }

    public function test_production_catalog_rerun_removes_only_seed_owned_templates_from_old_demo_placeholder(): void
    {
        Config::set('demo.allow_disposable_data_seeding', true);

        $this->seed(DeviceAttributeSeeder::class);
        $this->seed(DevicePresetSeeder::class);
        $this->seed(DeviceComponentCatalogSeeder::class);

        $modelNumber = ModelNumber::query()
            ->where('code', 'PIXEL8PRO-256-OBSIDIAN')
            ->firstOrFail();
        $seedOwnedTemplateIds = $modelNumber->componentTemplates()
            ->get()
            ->filter(fn (ModelNumberComponentTemplate $template): bool => (
                $template->metadata_json['catalog_seed_class'] ?? null
            ) === DeviceComponentCatalogSeeder::class)
            ->pluck('id')
            ->all();
        $operatorDefinition = ComponentDefinition::factory()->create([
            'name' => 'Operator Pixel Accessory',
            'metadata_json' => ['owner' => 'operator'],
        ]);
        $operatorTemplate = ModelNumberComponentTemplate::query()->create([
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $operatorDefinition->id,
            'expected_name' => 'Operator Pixel Accessory',
            'slot_name' => 'operator-pixel-accessory',
            'expected_qty' => 1,
            'is_required' => false,
            'sort_order' => 65000,
            'metadata_json' => ['owner' => 'operator'],
        ]);
        $operatorAttribute = AttributeDefinition::query()->create([
            'key' => 'operator_pixel_note',
            'label' => 'Operator Pixel Note',
            'datatype' => AttributeDefinition::DATATYPE_TEXT,
            'required_for_category' => false,
            'allow_custom_values' => true,
            'allow_asset_override' => true,
            'component_spec_display_mode' => AttributeDefinition::COMPONENT_SPEC_DISPLAY_VALUE_LABELS,
        ]);
        $operatorValue = ModelNumberAttribute::query()->create([
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $operatorAttribute->id,
            'value' => 'reviewed by operator',
            'raw_value' => 'reviewed by operator',
            'display_order' => 65000,
        ]);

        $this->assertNotEmpty($seedOwnedTemplateIds);

        Config::set('demo.allow_disposable_data_seeding', false);
        $this->seed(DeviceComponentCatalogSeeder::class);

        $this->assertDatabaseHas('model_numbers', ['id' => $modelNumber->id]);
        $this->assertDatabaseHas('model_number_component_templates', ['id' => $operatorTemplate->id]);
        $this->assertDatabaseHas('model_number_attributes', ['id' => $operatorValue->id]);
        $this->assertSame(
            0,
            ModelNumberComponentTemplate::query()->whereIn('id', $seedOwnedTemplateIds)->count()
        );
    }

    public function test_production_foundation_rerun_preserves_operator_catalog_additions(): void
    {
        Setting::query()->delete();
        $this->seed(ProductionFoundationSeeder::class);

        $enumDefinition = AttributeDefinition::query()
            ->where('datatype', AttributeDefinition::DATATYPE_ENUM)
            ->firstOrFail();
        $operatorOption = AttributeOption::query()->create([
            'attribute_definition_id' => $enumDefinition->id,
            'value' => 'operator_value',
            'label' => 'Operator Value',
            'active' => true,
            'sort_order' => 65000,
        ]);
        $operatorDefinition = AttributeDefinition::query()->create([
            'key' => 'operator_custom_spec',
            'label' => 'Operator Custom Spec',
            'datatype' => AttributeDefinition::DATATYPE_TEXT,
            'required_for_category' => false,
            'allow_custom_values' => true,
            'allow_asset_override' => true,
            'component_spec_display_mode' => AttributeDefinition::COMPONENT_SPEC_DISPLAY_VALUE_LABELS,
        ]);

        $modelNumber = ModelNumber::query()->firstOrFail();
        $operatorPreset = $modelNumber->model->modelNumbers()->create([
            'code' => 'OPERATOR-CUSTOM-PRESET',
            'label' => 'Operator Custom Preset',
        ]);
        $modelNumber->model->forceFill([
            'primary_model_number_id' => $operatorPreset->id,
            'model_number' => $operatorPreset->code,
        ])->save();
        $operatorModelValue = ModelNumberAttribute::query()->create([
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $operatorDefinition->id,
            'value' => 'operator-model-value',
            'raw_value' => 'operator-model-value',
            'display_order' => 65000,
        ]);

        $seededComponentDefinition = ComponentDefinition::query()
            ->whereHas('expectedTemplates', fn ($query) => $query->where('model_number_id', $modelNumber->id))
            ->firstOrFail();
        $operatorContribution = ComponentDefinitionAttribute::query()->create([
            'component_definition_id' => $seededComponentDefinition->id,
            'attribute_definition_id' => $operatorDefinition->id,
            'value' => 'operator-component-value',
            'raw_value' => 'operator-component-value',
            'resolves_to_spec' => true,
            'include_in_component_label' => false,
            'sort_order' => 65000,
        ]);
        $operatorChildDefinition = ComponentDefinition::factory()->create([
            'name' => 'Operator Child Component',
            'metadata_json' => ['owner' => 'operator'],
        ]);
        $operatorSubcomponent = ComponentDefinitionSubcomponentTemplate::query()->create([
            'parent_component_definition_id' => $seededComponentDefinition->id,
            'child_component_definition_id' => $operatorChildDefinition->id,
            'expected_name' => 'Operator Child Component',
            'expected_qty' => 1,
            'is_required' => false,
            'sort_order' => 65000,
            'metadata_json' => ['owner' => 'operator'],
        ]);
        $operatorExpectedComponent = ModelNumberComponentTemplate::query()->create([
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $operatorChildDefinition->id,
            'expected_name' => 'Operator Optional Component',
            'slot_name' => 'operator-slot',
            'expected_qty' => 1,
            'is_required' => false,
            'sort_order' => 65000,
            'metadata_json' => ['owner' => 'operator'],
        ]);

        $operatorWorkflowItem = TestType::query()->create([
            'name' => 'Operator Workflow Item',
            'slug' => 'operator-workflow-item',
            'display_order' => 65000,
            'applies_to_all' => true,
            'is_required' => false,
            'result_label_mode' => WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE,
        ]);
        $standardProfile = WorkflowProfile::query()
            ->where('slug', 'standard-diagnostics')
            ->firstOrFail();
        $operatorProfileItem = WorkflowProfileItem::query()->create([
            'workflow_profile_id' => $standardProfile->id,
            'workflow_item_id' => $operatorWorkflowItem->id,
            'sort_order' => 65000,
            'is_required' => false,
            'result_label_mode' => WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE,
        ]);
        $operatorProfile = WorkflowProfile::query()->create([
            'name' => 'Operator Profile',
            'slug' => 'operator-profile',
            'description' => 'Maintained by an administrator.',
            'is_active' => true,
            'is_default' => false,
            'blocks_sale_readiness' => false,
            'display_order' => 65000,
        ]);
        $operatorProfileOwnItem = WorkflowProfileItem::query()->create([
            'workflow_profile_id' => $operatorProfile->id,
            'workflow_item_id' => $operatorWorkflowItem->id,
            'sort_order' => 0,
            'is_required' => false,
            'result_label_mode' => WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE,
        ]);

        $this->seed(ProductionFoundationSeeder::class);

        $this->assertDatabaseHas('attribute_options', [
            'id' => $operatorOption->id,
            'active' => true,
            'label' => 'Operator Value',
        ]);
        $this->assertDatabaseHas('attribute_definitions', ['id' => $operatorDefinition->id]);
        $this->assertDatabaseHas('model_numbers', [
            'id' => $operatorPreset->id,
            'code' => 'OPERATOR-CUSTOM-PRESET',
            'label' => 'Operator Custom Preset',
        ]);
        $this->assertDatabaseHas('models', [
            'id' => $modelNumber->model_id,
            'primary_model_number_id' => $operatorPreset->id,
            'model_number' => 'OPERATOR-CUSTOM-PRESET',
        ]);
        $this->assertDatabaseHas('model_number_attributes', ['id' => $operatorModelValue->id]);
        $this->assertDatabaseHas('component_definition_attributes', ['id' => $operatorContribution->id]);
        $this->assertDatabaseHas('component_definition_subcomponent_templates', ['id' => $operatorSubcomponent->id]);
        $this->assertDatabaseHas('model_number_component_templates', ['id' => $operatorExpectedComponent->id]);
        $this->assertDatabaseHas('workflow_items', ['id' => $operatorWorkflowItem->id]);
        $this->assertDatabaseHas('workflow_profile_items', ['id' => $operatorProfileItem->id]);
        $this->assertDatabaseHas('workflow_profiles', ['id' => $operatorProfile->id]);
        $this->assertDatabaseHas('workflow_profile_items', ['id' => $operatorProfileOwnItem->id]);
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

    public function test_catalog_seeds_structured_wireless_and_camera_details(): void
    {
        Config::set('demo.allow_disposable_data_seeding', true);

        $this->seed(DeviceAttributeSeeder::class);
        $this->seed(DevicePresetSeeder::class);
        $this->seed(DeviceComponentCatalogSeeder::class);

        $wifiStandard = AttributeDefinition::query()->where('key', 'wifi_standard_max')->firstOrFail();
        $bluetoothVersion = AttributeDefinition::query()->where('key', 'bluetooth_version')->firstOrFail();
        $cellularGeneration = AttributeDefinition::query()->where('key', 'cellular_generation_max')->firstOrFail();
        $cameraMegapixels = AttributeDefinition::query()->where('key', 'camera_megapixels')->firstOrFail();
        $this->assertNotNull(AttributeDefinition::query()->where('key', 'camera_aperture')->first());
        $this->assertNotNull(AttributeDefinition::query()->where('key', 'camera_autofocus')->first());
        $this->assertNotNull(AttributeDefinition::query()->where('key', 'camera_ois')->first());
        $wirelessAc = ComponentDefinition::query()->where('name', 'Wireless - 802.11ac')->firstOrFail();
        $wirelessAx = ComponentDefinition::query()->where('name', 'Wireless - 802.11ax')->firstOrFail();
        $main64 = ComponentDefinition::query()->where('name', 'Camera - Main - 64MP')->firstOrFail();
        $depth5 = ComponentDefinition::query()->where('name', 'Camera - Depth - 5MP')->firstOrFail();
        $pixelSelfie = ComponentDefinition::query()->where('name', 'Camera - Selfie - 10.5MP')->firstOrFail();
        $pixelModelNumber = ModelNumber::query()->where('code', 'PIXEL8PRO-256-OBSIDIAN')->firstOrFail();

        $this->assertSame(AttributeDefinition::COMPONENT_SPEC_DISPLAY_COMPONENT_LABELS, $cameraMegapixels->component_spec_display_mode);
        $this->assertSame('802.11ac', $wirelessAc->spec_display_label);
        $this->assertSame('802.11ax', $wirelessAx->spec_display_label);
        $this->assertSame('Main 64MP', $main64->spec_display_label);
        $this->assertSame('Depth 5MP', $depth5->spec_display_label);
        $this->assertSame('Selfie 10.5MP', $pixelSelfie->spec_display_label);

        $this->assertDatabaseHas('attribute_options', [
            'attribute_definition_id' => $wifiStandard->id,
            'value' => '802.11be',
            'label' => '802.11be',
            'active' => true,
        ]);
        $this->assertDatabaseHas('attribute_options', [
            'attribute_definition_id' => $bluetoothVersion->id,
            'value' => '6.1',
            'label' => 'Bluetooth 6.1',
            'active' => true,
        ]);
        $this->assertDatabaseHas('attribute_options', [
            'attribute_definition_id' => $cellularGeneration->id,
            'value' => '4g_lte',
            'label' => '4G LTE',
            'active' => true,
        ]);
        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $wirelessAc->id,
            'attribute_definition_id' => $wifiStandard->id,
            'value' => '802.11ac',
            'resolves_to_spec' => true,
            'include_in_component_label' => true,
        ]);
        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $main64->id,
            'attribute_definition_id' => $cameraMegapixels->id,
            'value' => '64',
            'resolves_to_spec' => true,
            'include_in_component_label' => true,
        ]);
        $this->assertDatabaseHas('model_number_component_templates', [
            'model_number_id' => $pixelModelNumber->id,
            'component_definition_id' => $pixelSelfie->id,
        ]);
    }

    public function test_catalog_seeds_galaxy_a51_128gb_expected_components(): void
    {
        $this->seed(DeviceAttributeSeeder::class);
        $this->seed(DevicePresetSeeder::class);
        $this->seed(DeviceComponentCatalogSeeder::class);

        $modelNumber = ModelNumber::query()->where('code', 'SM-A515F/DSN-4GB-128GB')->firstOrFail();
        $logicBoard = ComponentDefinition::query()
            ->where('name', 'Logic Board - Samsung Galaxy A51 SM-A515F/DSN')
            ->firstOrFail();
        $wirelessAc = ComponentDefinition::query()->where('name', 'Wireless - 802.11ac')->firstOrFail();
        $selfie32 = ComponentDefinition::query()->where('name', 'Camera - Selfie - 32MP')->firstOrFail();
        $main48 = ComponentDefinition::query()->where('name', 'Camera - Main - 48MP')->firstOrFail();
        $display = ComponentDefinition::query()
            ->where('name', 'Display 6.5 1080x2400 Super AMOLED 60Hz')
            ->firstOrFail();
        $battery = ComponentDefinition::query()->where('name', 'Battery 4000 mAh')->firstOrFail();
        $bluetoothVersion = AttributeDefinition::query()->where('key', 'bluetooth_version')->firstOrFail();
        $cellularGeneration = AttributeDefinition::query()->where('key', 'cellular_generation_max')->firstOrFail();
        $nfc = AttributeDefinition::query()->where('key', 'nfc')->firstOrFail();

        foreach ([
            $logicBoard,
            $display,
            $battery,
            $selfie32,
            $main48,
        ] as $definition) {
            $this->assertDatabaseHas('model_number_component_templates', [
                'model_number_id' => $modelNumber->id,
                'component_definition_id' => $definition->id,
            ]);
        }

        $this->assertDatabaseHas('component_definition_subcomponent_templates', [
            'parent_component_definition_id' => $logicBoard->id,
            'child_component_definition_id' => $wirelessAc->id,
            'expected_qty' => 1,
        ]);
        $this->assertDatabaseHas('model_number_attributes', [
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $bluetoothVersion->id,
            'value' => '5.0',
        ]);
        $this->assertDatabaseHas('model_number_attributes', [
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $cellularGeneration->id,
            'value' => '4g_lte',
        ]);
        $this->assertDatabaseHas('model_number_attributes', [
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $nfc->id,
            'value' => '1',
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
