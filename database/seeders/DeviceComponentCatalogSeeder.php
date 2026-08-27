<?php

namespace Database\Seeders;

use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionAttribute;
use App\Models\ComponentDefinitionSubcomponentTemplate;
use App\Models\ModelNumber;
use App\Models\ModelNumberAttribute;
use App\Models\ModelNumberComponentTemplate;
use App\Services\ModelAttributes\AttributeValueService;
use App\Services\ModelAttributes\ModelAttributeManager;
use Database\Seeders\Concerns\ProvidesDeviceCatalogData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeviceComponentCatalogSeeder extends Seeder
{
    use ProvidesDeviceCatalogData;

    private const COMPONENT_DEFINITION_RENAMES = [
        'Webcam Module' => 'Webcam',
        'Wireless Module' => 'Wireless',
    ];

    public function run(): void
    {
        if (!Schema::hasTable('component_definitions') || !Schema::hasTable('model_number_component_templates')) {
            return;
        }

        DB::transaction(function (): void {
            $definitions = $this->seedComponentDefinitions();
            $this->seedModelNumberTemplates($definitions);
        });
    }

    /**
     * @return array<string,ComponentDefinition>
     */
    private function seedComponentDefinitions(): array
    {
        $categories = Category::query()
            ->where('category_type', 'component')
            ->get()
            ->keyBy('name');
        $attributeDefinitions = AttributeDefinition::query()
            ->whereNull('deprecated_at')
            ->whereNull('hidden_at')
            ->get()
            ->keyBy('key');
        $valueService = app(AttributeValueService::class);
        $seeded = [];

        $this->renameLegacyComponentDefinitions();

        foreach ($this->componentDefinitions() as $name => $config) {
            /** @var ComponentDefinition $definition */
            $definition = ComponentDefinition::withTrashed()->firstOrNew(['name' => $name]);
            $definition->fill([
                'category_id' => $categories->get($config['category'])?->id,
                'manufacturer_id' => null,
                'model_number' => null,
                'part_code' => null,
                'spec_summary' => $config['summary'] ?? null,
                'spec_display_label' => array_key_exists('spec_display_label', $config)
                    ? $config['spec_display_label']
                    : $definition->spec_display_label,
                'serial_tracking_mode' => $config['serial_tracking_mode'] ?? 'optional',
                'placement_mode' => $config['placement_mode'] ?? ComponentDefinition::PLACEMENT_EITHER,
                'is_active' => true,
                'metadata_json' => array_replace(
                    $this->seedMetadata('component-definition:'.$name),
                    $definition->metadata_json ?: []
                ),
                'created_by' => $definition->exists ? $definition->created_by : null,
                'updated_by' => null,
            ]);

            if ($definition->trashed()) {
                $definition->restore();
            }

            $definition->save();

            $assignedAttributeIds = [];
            foreach ($config['attributes'] ?? [] as $key => $attributeConfig) {
                $attribute = $attributeDefinitions->get($key);

                if (!$attribute) {
                    continue;
                }

                $tuple = $valueService->validateAndNormalize(
                    $attribute,
                    $attributeConfig['value'],
                    'component_attributes'
                );

                ComponentDefinitionAttribute::updateOrCreate(
                    [
                        'component_definition_id' => $definition->id,
                        'attribute_definition_id' => $attribute->id,
                    ],
                    [
                        'value' => $tuple->value,
                        'raw_value' => $tuple->rawValue,
                        'attribute_option_id' => $tuple->attributeOptionId,
                        'resolves_to_spec' => (bool) ($attributeConfig['resolves_to_spec'] ?? false),
                        'include_in_component_label' => (bool) ($attributeConfig['include_in_component_label'] ?? false),
                        'sort_order' => count($assignedAttributeIds),
                    ]
                );

                $assignedAttributeIds[] = $attribute->id;
            }
            $seeded[$name] = $definition;
        }

        foreach ($this->componentDefinitions() as $name => $config) {
            $definition = $seeded[$name] ?? null;

            if (!$definition) {
                continue;
            }

            $assignedSubcomponentTemplateIds = [];
            foreach ($config['subcomponents'] ?? [] as $index => $templateConfig) {
                $childDefinition = $seeded[$templateConfig['definition']] ?? null;

                if (!$childDefinition) {
                    continue;
                }

                $template = ComponentDefinitionSubcomponentTemplate::firstOrNew([
                    'parent_component_definition_id' => $definition->id,
                    'child_component_definition_id' => $childDefinition->id,
                    'expected_name' => $templateConfig['expected_name'] ?? $childDefinition->name,
                ]);

                $template->fill([
                    'expected_qty' => max(1, (int) ($templateConfig['qty'] ?? 1)),
                    'is_required' => (bool) ($templateConfig['required'] ?? true),
                    'sort_order' => $index,
                    'metadata_json' => array_replace(
                        $template->metadata_json ?: [],
                        $templateConfig['metadata'] ?? [],
                        $this->seedMetadata(sprintf(
                            'component-subcomponent:%s:%s:%s',
                            $name,
                            $childDefinition->name,
                            $templateConfig['expected_name'] ?? $childDefinition->name
                        ))
                    ),
                    'notes' => $templateConfig['notes'] ?? null,
                ]);
                $template->save();

                $assignedSubcomponentTemplateIds[] = $template->id;
            }

            $this->deleteSeedOwnedSubcomponentTemplates($definition, $assignedSubcomponentTemplateIds);
        }

        return $seeded;
    }

    private function renameLegacyComponentDefinitions(): void
    {
        foreach (self::COMPONENT_DEFINITION_RENAMES as $legacyName => $currentName) {
            $legacy = ComponentDefinition::withTrashed()->where('name', $legacyName)->first();

            if (!$legacy) {
                continue;
            }

            $current = ComponentDefinition::withTrashed()->where('name', $currentName)->first();

            if ($current && $current->id !== $legacy->id) {
                continue;
            }

            $legacy->name = $currentName;
            $legacy->save();
        }
    }

    /**
     * @param array<string,ComponentDefinition> $definitions
     */
    private function seedModelNumberTemplates(array $definitions): void
    {
        foreach ($this->modelNumberComponentTemplates() as $modelNumberCode => $templates) {
            $modelNumber = ModelNumber::query()->where('code', $modelNumberCode)->first();

            if (!$modelNumber) {
                continue;
            }

            if (!$this->modelNumberCodeIsSeedable($modelNumberCode)) {
                $this->deleteSeedOwnedModelTemplates($modelNumber, []);

                continue;
            }

            $assignedTemplateIds = [];

            foreach ($templates as $index => $templateConfig) {
                $definition = $definitions[$templateConfig['definition']] ?? null;

                if (!$definition) {
                    continue;
                }

                $template = ModelNumberComponentTemplate::firstOrNew([
                    'model_number_id' => $modelNumber->id,
                    'component_definition_id' => $definition->id,
                    'expected_name' => $templateConfig['expected_name'] ?? $definition->name,
                    'slot_name' => $templateConfig['slot_name'] ?? null,
                ]);

                $template->fill([
                    'expected_qty' => max(1, (int) ($templateConfig['qty'] ?? 1)),
                    'is_required' => (bool) ($templateConfig['required'] ?? true),
                    'sort_order' => $index,
                    'metadata_json' => array_replace(
                        $template->metadata_json ?: [],
                        $templateConfig['metadata'] ?? [],
                        $this->seedMetadata(sprintf(
                            'model-component:%s:%s:%s:%s',
                            $modelNumberCode,
                            $definition->name,
                            $templateConfig['slot_name'] ?? '',
                            $templateConfig['expected_name'] ?? $definition->name
                        ))
                    ),
                    'notes' => $templateConfig['notes'] ?? null,
                ]);
                $template->save();

                $assignedTemplateIds[] = $template->id;
            }

            $this->deleteSeedOwnedModelTemplates($modelNumber, $assignedTemplateIds);

            $this->removeComponentBackedModelAttributes($modelNumber);
        }
    }

    /**
     * @param array<int,int> $assignedTemplateIds
     */
    private function deleteSeedOwnedSubcomponentTemplates(
        ComponentDefinition $definition,
        array $assignedTemplateIds
    ): void {
        ComponentDefinitionSubcomponentTemplate::query()
            ->where('parent_component_definition_id', $definition->id)
            ->get()
            ->each(function (ComponentDefinitionSubcomponentTemplate $template) use ($assignedTemplateIds): void {
                if (in_array((int) $template->id, $assignedTemplateIds, true)) {
                    return;
                }

                $metadata = $template->metadata_json ?: [];

                if (($metadata['catalog_seed_class'] ?? null) === self::class) {
                    $template->delete();
                }
            });
    }

    /**
     * @param array<int,int> $assignedTemplateIds
     */
    private function deleteSeedOwnedModelTemplates(
        ModelNumber $modelNumber,
        array $assignedTemplateIds
    ): void {
        ModelNumberComponentTemplate::query()
            ->where('model_number_id', $modelNumber->id)
            ->get()
            ->each(function (ModelNumberComponentTemplate $template) use ($assignedTemplateIds): void {
                if (in_array((int) $template->id, $assignedTemplateIds, true)) {
                    return;
                }

                $metadata = $template->metadata_json ?: [];

                if (($metadata['catalog_seed_class'] ?? null) === self::class) {
                    $template->delete();
                }
            });
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function componentDefinitions(): array
    {
        return [
            'Motherboard - HP ProBook 450 G8 - i5-1135G7' => $this->logicBoard('Intel Core i5-1135G7', 4, 'Intel Iris Xe Graphics', [
                $this->subcomponent('USB-A Port - USB 3.1 Gen1 - Sleep/Charge'),
                $this->subcomponent('USB-A Port - USB 3.1 Gen1', 2),
                $this->subcomponent('USB-C Port - USB 3.1 Gen2 - DP 1.4 Alt - PD'),
                $this->subcomponent('HDMI Port - 1.4b'),
                $this->subcomponent('3.5mm Port - Headset Combo'),
            ]),
            'Motherboard - HP ProBook 450 G7 - i5-10210U' => $this->logicBoard('Intel Core i5-10210U', 4, 'Intel UHD Graphics', [
                $this->subcomponent('USB-A Port - USB 3.1 Gen1', 2),
                $this->subcomponent('USB-C Port - USB 3.1 Gen1 - DP Alt - PD'),
                $this->subcomponent('HDMI Port - 1.4b'),
                $this->subcomponent('3.5mm Port - Headset Combo'),
            ]),
            'Motherboard - HP ProBook 450 G6 - i5-8265U' => $this->logicBoard('Intel Core i5-8265U', 4, 'Intel UHD Graphics 620', [
                $this->subcomponent('USB-A Port - USB 3.2 Gen1', 2),
                $this->subcomponent('USB-C Port - USB 3.2 Gen1 - DP Alt - PD'),
                $this->subcomponent('HDMI Port - 1.4'),
                $this->subcomponent('3.5mm Port - Headset Combo'),
            ]),
            'Motherboard - HP ProBook 430 G7 - i5-10210U' => $this->logicBoard('Intel Core i5-10210U', 4, 'Intel UHD Graphics', [
                $this->subcomponent('USB-A Port - USB 3.1 Gen1', 2),
                $this->subcomponent('USB-C Port - USB 3.1 Gen1 - DP Alt - PD'),
                $this->subcomponent('HDMI Port - 1.4b'),
                $this->subcomponent('3.5mm Port - Headset Combo'),
            ]),
            'Motherboard - HP ProBook 430 G6 - i5-8265U' => $this->logicBoard('Intel Core i5-8265U', 4, 'Intel UHD Graphics 620', [
                $this->subcomponent('USB-A Port - USB 3.2 Gen1', 2),
                $this->subcomponent('USB-A Port - USB 2.0'),
                $this->subcomponent('USB-C Port - USB-C Gen1 - DisplayPort'),
                $this->subcomponent('HDMI Port - 1.4'),
                $this->subcomponent('RJ-45 Ethernet Port - 1GbE'),
                $this->subcomponent('SD Card Reader'),
                $this->subcomponent('3.5mm Port - Headset Combo'),
            ]),
            'Motherboard - HP ProBook 430 G3 - i3-6100U' => $this->logicBoard('Intel Core i3-6100U', 2, 'Intel HD Graphics 520', [
                $this->subcomponent('USB-A Port - USB 3.2 Gen1', 2),
                $this->subcomponent('USB-A Port - USB 2.0'),
                $this->subcomponent('HDMI Port - 1.4'),
                $this->subcomponent('VGA Port'),
                $this->subcomponent('RJ-45 Ethernet Port - 1GbE'),
                $this->subcomponent('3.5mm Port - Headset Combo'),
            ]),
            'Logic Board - Surface Pro 4 - i5-6300U' => $this->logicBoard('Intel Core i5-6300U', 2, 'Intel HD Graphics 520', [
                $this->subcomponent('RAM 4GB LPDDR3'),
                $this->subcomponent('Wireless'),
                $this->subcomponent('USB-A Port - USB 3.0'),
                $this->subcomponent('Mini DisplayPort'),
                $this->subcomponent('Surface Connect Port'),
                $this->subcomponent('3.5mm Port - Headset Combo'),
            ]),
            'Logic Board - Surface Pro 5 - i5-7300U' => $this->logicBoard('Intel Core i5-7300U', 2, 'Intel HD Graphics 620', [
                $this->subcomponent('RAM 4GB LPDDR3'),
                $this->subcomponent('Wireless'),
                $this->subcomponent('USB-A Port - USB 3.0'),
                $this->subcomponent('Mini DisplayPort'),
                $this->subcomponent('Surface Connect Port'),
                $this->subcomponent('3.5mm Port - Headset Combo'),
            ]),
            'Logic Board - Samsung Galaxy A5 SM-A520F' => $this->logicBoard(null, null, null, [
                $this->subcomponent('RAM 3GB LPDDR4X'),
                $this->subcomponent('Storage 32GB UFS'),
                $this->subcomponent('Wireless'),
                $this->subcomponent('USB-C Charging/Data Port'),
            ]),
            'Logic Board - Samsung Galaxy A51 SM-A515F/DSN' => $this->logicBoard(null, null, null, [
                $this->subcomponent('RAM 4GB LPDDR4X'),
                $this->subcomponent('Storage 128GB UFS'),
                $this->subcomponent('Wireless - 802.11ac'),
                $this->subcomponent('USB-C Charging/Data Port'),
                $this->subcomponent('3.5mm Port - Headset Combo'),
            ]),
            'Logic Board - iPhone 12' => $this->logicBoard(null, null, null, [
                $this->subcomponent('RAM 4GB LPDDR4X'),
                $this->subcomponent('Storage 128GB UFS'),
                $this->subcomponent('Wireless'),
                $this->subcomponent('Lightning Port'),
            ]),
            'Logic Board - Pixel 8 Pro' => $this->logicBoard(null, null, null, [
                $this->subcomponent('RAM 12GB LPDDR5X'),
                $this->subcomponent('Storage 256GB UFS'),
                $this->subcomponent('Wireless'),
                $this->subcomponent('USB-C Charging/Data Port'),
            ]),

            'RAM 3GB LPDDR4X' => $this->memory(3, 'lpddr4x'),
            'RAM 4GB DDR4' => $this->memory(4, 'ddr4'),
            'RAM 4GB LPDDR3' => $this->memory(4, 'lpddr3'),
            'RAM 4GB LPDDR4X' => $this->memory(4, 'lpddr4x'),
            'RAM 8GB DDR4' => $this->memory(8, 'ddr4'),
            'RAM 12GB LPDDR5X' => $this->memory(12, 'lpddr5x'),
            'RAM - Generic' => ['category' => 'Memory'],

            'Storage 32GB UFS' => $this->storage(32, 'ufs'),
            'Storage 128GB SATA SSD' => $this->storage(128, 'ssd'),
            'Storage 128GB NVMe' => $this->storage(128, 'nvme'),
            'Storage 128GB UFS' => $this->storage(128, 'ufs'),
            'Storage 256GB NVMe' => $this->storage(256, 'nvme'),
            'Storage 256GB UFS' => $this->storage(256, 'ufs'),
            'SSD - Generic' => $this->storageType('ssd'),
            'HDD - Generic' => $this->storageType('hdd'),

            'Display 15.6 FHD IPS 60Hz' => $this->display(15.6, '1920 x 1080', 'ips', 60),
            'Display 13.3 FHD IPS 60Hz' => $this->display(13.3, '1920 x 1080', 'ips', 60),
            'Display 13.3 HD TN 60Hz' => $this->display(13.3, '1366 x 768', 'tn', 60),
            'Display 12.3 2736x1824 IPS 60Hz' => $this->display(12.3, '2736 x 1824', 'ips', 60),
            'Display 5.2 FHD AMOLED 60Hz' => $this->display(5.2, '1920 x 1080', 'amoled', 60),
            'Display 6.5 1080x2400 Super AMOLED 60Hz' => $this->display(6.5, '1080 x 2400', 'amoled', 60),
            'Display 6.1 2532x1170 OLED 60Hz' => $this->display(6.1, '2532 x 1170', 'oled', 60),
            'Display 6.7 2992x1344 OLED 120Hz' => $this->display(6.7, '2992 x 1344', 'oled', 120),

            'Battery 45 Wh' => $this->batteryWh(45),
            'Battery 38 Wh' => $this->batteryWh(38),
            'Battery 3000 mAh' => $this->batteryMah(3000),
            'Battery 4000 mAh' => $this->batteryMah(4000),
            'Battery 2815 mAh' => $this->batteryMah(2815),
            'Battery 5050 mAh' => $this->batteryMah(5050),
            'Battery - Generic' => ['category' => 'Battery'],

            'Keyboard US' => $this->keyboard('us'),
            'Keyboard QWERTY' => $this->keyboard('qwerty'),
            'Keyboard US International' => $this->keyboard('qwerty_us_intl'),
            'Keyboard - Generic' => ['category' => 'Input'],
            'Touchpad' => ['category' => 'Input'],

            'Camera - Generic' => ['category' => 'Camera'],
            'Webcam' => $this->camera('webcam', 'selfie', null),
            'Camera - Selfie - 10MP' => $this->camera('front', 'selfie', 10),
            'Camera - Selfie - 10.5MP' => $this->camera('front', 'selfie', 10.5),
            'Camera - Selfie - 12MP' => $this->camera('front', 'selfie', 12),
            'Camera - Selfie - 16MP' => $this->camera('front', 'selfie', 16),
            'Camera - Selfie - 20MP' => $this->camera('front', 'selfie', 20),
            'Camera - Selfie - 32MP' => $this->camera('front', 'selfie', 32),
            'Camera - Main - 12MP' => $this->camera('rear', 'main', 12),
            'Camera - Main - 16MP' => $this->camera('rear', 'main', 16),
            'Camera - Main - 48MP' => $this->camera('rear', 'main', 48),
            'Camera - Main - 50MP' => $this->camera('rear', 'main', 50),
            'Camera - Main - 64MP' => $this->camera('rear', 'main', 64),
            'Camera - Ultrawide - 8MP' => $this->camera('rear', 'ultrawide', 8),
            'Camera - Ultrawide - 12MP' => $this->camera('rear', 'ultrawide', 12),
            'Camera - Ultrawide - 48MP' => $this->camera('rear', 'ultrawide', 48),
            'Camera - Telephoto - 48MP' => $this->camera('rear', 'telephoto', 48),
            'Camera - Macro - 5MP' => $this->camera('rear', 'macro', 5),
            'Camera - Depth - 5MP' => $this->camera('rear', 'depth', 5),

            'Speaker' => ['category' => 'Audio'],
            'Microphone' => ['category' => 'Audio'],
            '3.5mm Port - Headset Combo' => $this->audioPort('headset_combo', 'trrs_ctia'),
            '3.5mm Port - Headphone Out' => $this->audioPort('headphone_out', 'trs'),
            '3.5mm Port - Microphone In' => $this->audioPort('microphone_in', 'trs'),
            '3.5mm Port - Line In' => $this->audioPort('line_in', 'trs'),
            '3.5mm Port - Line Out' => $this->audioPort('line_out', 'trs'),
            'Wireless' => ['category' => 'Network'],
            'Wireless - Generic' => ['category' => 'Network'],
            'Wireless - 802.11n' => $this->wireless('802.11n'),
            'Wireless - 802.11ac' => $this->wireless('802.11ac'),
            'Wireless - 802.11ax' => $this->wireless('802.11ax'),
            'Wireless - 802.11be' => $this->wireless('802.11be'),
            'Bluetooth - Generic' => ['category' => 'Network'],

            'USB-A Port - Generic' => $this->port('usb_a'),
            'USB-A Port - USB 2.0' => $this->port('usb_a', ['usb_standard' => 'usb_2_0']),
            'USB-A Port - USB 3.0' => $this->port('usb_a', ['usb_standard' => 'usb_3_0']),
            'USB-A Port - USB 3.1 Gen1' => $this->port('usb_a', ['usb_standard' => 'usb_3_1_gen1']),
            'USB-A Port - USB 3.1 Gen1 - Sleep/Charge' => $this->port('usb_a', [
                'usb_standard' => 'usb_3_1_gen1',
                'sleep_and_charge' => true,
            ]),
            'USB-A Port - USB 3.2 Gen1' => $this->port('usb_a', ['usb_standard' => 'usb_3_2_gen1']),
            'USB-C Port - USB 3.1 Gen1 - DP Alt - PD' => $this->port('usb_c', [
                'usb_standard' => 'usb_3_1_gen1',
                'displayport_alt_mode' => true,
                'power_delivery' => true,
            ]),
            'USB-C Port - USB 3.1 Gen2 - DP 1.4 Alt - PD' => $this->port('usb_c', [
                'usb_standard' => 'usb_3_1_gen2',
                'displayport_alt_mode' => true,
                'displayport_version' => '1.4',
                'power_delivery' => true,
            ]),
            'USB-C Port - USB 3.2 Gen1 - DP Alt - PD' => $this->port('usb_c', [
                'usb_standard' => 'usb_3_2_gen1',
                'displayport_alt_mode' => true,
                'power_delivery' => true,
            ]),
            'USB-C Port - USB-C Gen1 - DisplayPort' => $this->port('usb_c', [
                'usb_standard' => 'usb_3_1_gen1',
                'displayport_alt_mode' => true,
            ]),
            'USB-C Port - Generic' => $this->port('usb_c'),
            'USB-C Charging/Data Port' => $this->port('usb_c'),
            'HDMI Port' => $this->port('hdmi'),
            'HDMI Port - 1.4' => $this->port('hdmi', ['hdmi_version' => '1.4']),
            'HDMI Port - 1.4b' => $this->port('hdmi', ['hdmi_version' => '1.4b']),
            'VGA Port' => $this->port('vga'),
            'RJ-45 Ethernet Port - 1GbE' => $this->ethernetPort('1gbe'),
            'RJ-45 Ethernet Port - 2.5GbE' => $this->ethernetPort('2_5gbe'),
            'RJ-45 Ethernet Port - 5GbE' => $this->ethernetPort('5gbe'),
            'RJ-45 Ethernet Port - 10GbE' => $this->ethernetPort('10gbe'),
            'SD Card Reader' => $this->port('sd_card'),
            'DisplayPort' => $this->port('displayport'),
            'Mini DisplayPort' => $this->port('mini_displayport'),
            'Surface Connect Port' => $this->port('surface_connect'),
            'Lightning Port' => $this->port('lightning'),
            'eSATA Port' => $this->port('esata'),
        ];
    }

    /**
     * @return array<string,array<int,array<string,mixed>>>
     */
    private function modelNumberComponentTemplates(): array
    {
        return [
            '2E9F8EA#ABH' => [
                $this->template('Motherboard - HP ProBook 450 G8 - i5-1135G7'),
                $this->template('RAM 8GB DDR4'),
                $this->template('Storage 256GB NVMe'),
                $this->template('Display 15.6 FHD IPS 60Hz'),
                $this->template('Battery 45 Wh'),
                $this->template('Keyboard US'),
                $this->template('Touchpad'),
                $this->template('Webcam'),
                $this->template('Speaker'),
                $this->template('Microphone'),
                $this->template('Wireless'),
            ],
            '8VU81EA#ABH' => [
                $this->template('Motherboard - HP ProBook 450 G7 - i5-10210U'),
                $this->template('RAM 8GB DDR4'),
                $this->template('Storage 256GB NVMe'),
                $this->template('Display 15.6 FHD IPS 60Hz'),
                $this->template('Battery 45 Wh'),
                $this->template('Keyboard US'),
                $this->template('Touchpad'),
                $this->template('Webcam'),
                $this->template('Speaker'),
                $this->template('Microphone'),
                $this->template('Wireless'),
            ],
            '5PP65EA#ABH' => [
                $this->template('Motherboard - HP ProBook 450 G6 - i5-8265U'),
                $this->template('RAM 8GB DDR4'),
                $this->template('Storage 256GB NVMe'),
                $this->template('Display 15.6 FHD IPS 60Hz'),
                $this->template('Battery 45 Wh'),
                $this->template('Keyboard US'),
                $this->template('Touchpad'),
                $this->template('Webcam'),
                $this->template('Speaker'),
                $this->template('Microphone'),
                $this->template('Wireless'),
            ],
            '8VT42EA#ABH' => [
                $this->template('Motherboard - HP ProBook 430 G7 - i5-10210U'),
                $this->template('RAM 8GB DDR4'),
                $this->template('Storage 256GB NVMe'),
                $this->template('Display 13.3 FHD IPS 60Hz'),
                $this->template('Battery 45 Wh'),
                $this->template('Keyboard US'),
                $this->template('Touchpad'),
                $this->template('Webcam'),
                $this->template('Speaker'),
                $this->template('Microphone'),
                $this->template('Wireless'),
            ],
            '5TK76EA#ABH' => [
                $this->template('Motherboard - HP ProBook 430 G6 - i5-8265U'),
                $this->template('RAM 8GB DDR4'),
                $this->template('Storage 128GB NVMe'),
                $this->template('Display 13.3 FHD IPS 60Hz'),
                $this->template('Battery 45 Wh'),
                $this->template('Keyboard QWERTY'),
                $this->template('Touchpad'),
                $this->template('Webcam'),
                $this->template('Speaker'),
                $this->template('Microphone'),
                $this->template('Wireless'),
            ],
            'HP-430G3-I3-4-128' => [
                $this->template('Motherboard - HP ProBook 430 G3 - i3-6100U'),
                $this->template('RAM 4GB DDR4'),
                $this->template('Storage 128GB SATA SSD'),
                $this->template('Display 13.3 HD TN 60Hz'),
                $this->template('Keyboard US International'),
                $this->template('Touchpad'),
                $this->template('Webcam'),
                $this->template('Speaker'),
                $this->template('Microphone'),
                $this->template('Wireless'),
            ],
            'MS-SURFPRO4-I5-4-128' => [
                $this->template('Logic Board - Surface Pro 4 - i5-6300U'),
                $this->template('Storage 128GB NVMe'),
                $this->template('Display 12.3 2736x1824 IPS 60Hz'),
                $this->template('Battery 38 Wh'),
                $this->template('Webcam'),
                $this->template('Speaker'),
                $this->template('Microphone'),
            ],
            'MS-SURFPRO5-I5-4-128' => [
                $this->template('Logic Board - Surface Pro 5 - i5-7300U'),
                $this->template('Storage 128GB NVMe'),
                $this->template('Display 12.3 2736x1824 IPS 60Hz'),
                $this->template('Battery 45 Wh'),
                $this->template('Webcam'),
                $this->template('Speaker'),
                $this->template('Microphone'),
            ],
            'SM-A520F' => [
                $this->template('Logic Board - Samsung Galaxy A5 SM-A520F'),
                $this->template('Display 5.2 FHD AMOLED 60Hz'),
                $this->template('Battery 3000 mAh'),
                $this->template('Camera - Selfie - 16MP'),
                $this->template('Camera - Main - 16MP'),
                $this->template('Speaker'),
                $this->template('Microphone'),
            ],
            'SM-A515F/DSN-4GB-128GB' => [
                $this->template('Logic Board - Samsung Galaxy A51 SM-A515F/DSN'),
                $this->template('Display 6.5 1080x2400 Super AMOLED 60Hz'),
                $this->template('Battery 4000 mAh'),
                $this->template('Camera - Selfie - 32MP'),
                $this->template('Camera - Main - 48MP'),
                $this->template('Camera - Ultrawide - 12MP'),
                $this->template('Camera - Macro - 5MP'),
                $this->template('Camera - Depth - 5MP'),
                $this->template('Speaker'),
                $this->template('Microphone'),
            ],
            'IP12-128-BLUE' => [
                $this->template('Logic Board - iPhone 12'),
                $this->template('Display 6.1 2532x1170 OLED 60Hz'),
                $this->template('Battery 2815 mAh'),
                $this->template('Camera - Selfie - 12MP'),
                $this->template('Camera - Main - 12MP'),
                $this->template('Camera - Ultrawide - 12MP'),
                $this->template('Speaker'),
                $this->template('Microphone'),
            ],
            'PIXEL8PRO-256-OBSIDIAN' => [
                $this->template('Logic Board - Pixel 8 Pro'),
                $this->template('Display 6.7 2992x1344 OLED 120Hz'),
                $this->template('Battery 5050 mAh'),
                $this->template('Camera - Selfie - 10.5MP'),
                $this->template('Camera - Main - 50MP'),
                $this->template('Camera - Ultrawide - 48MP'),
                $this->template('Camera - Telephoto - 48MP'),
                $this->template('Speaker'),
                $this->template('Microphone'),
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function logicBoard(?string $cpuModel, ?int $coreCount, ?string $gpuModel, array $subcomponents): array
    {
        $attributes = [];

        if ($cpuModel !== null) {
            $attributes['cpu_model'] = ['value' => $cpuModel, 'resolves_to_spec' => true];
        }

        if ($coreCount !== null) {
            $attributes['cpu_core_count'] = ['value' => $coreCount, 'resolves_to_spec' => true];
        }

        if ($gpuModel !== null) {
            $attributes['gpu_model'] = ['value' => $gpuModel, 'resolves_to_spec' => true];
        }

        return [
            'category' => 'Logic Board',
            'placement_mode' => ComponentDefinition::PLACEMENT_ASSET_ONLY,
            'attributes' => $attributes,
            'subcomponents' => $subcomponents,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function memory(int $sizeGb, string $type): array
    {
        return [
            'category' => 'Memory',
            'attributes' => [
                'ram_size_gb' => ['value' => $sizeGb, 'resolves_to_spec' => true],
                'ram_type' => ['value' => $type, 'resolves_to_spec' => true],
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function storage(int $capacityGb, string $type): array
    {
        return [
            'category' => 'Storage',
            'attributes' => [
                'storage_capacity_gb' => ['value' => $capacityGb, 'resolves_to_spec' => true],
                'storage_type' => ['value' => $type, 'resolves_to_spec' => true],
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function storageType(string $type): array
    {
        return [
            'category' => 'Storage',
            'attributes' => [
                'storage_type' => ['value' => $type, 'resolves_to_spec' => true],
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function display(float $size, string $resolution, string $panelType, int $refreshRate): array
    {
        return [
            'category' => 'Display',
            'attributes' => [
                'display_size_inches' => ['value' => $size, 'resolves_to_spec' => true],
                'display_resolution' => ['value' => $resolution, 'resolves_to_spec' => true],
                'display_panel_type' => ['value' => $panelType, 'resolves_to_spec' => true],
                'display_refresh_rate_hz' => ['value' => $refreshRate, 'resolves_to_spec' => true],
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function batteryWh(float $capacityWh): array
    {
        return [
            'category' => 'Battery',
            'attributes' => [
                'battery_capacity_wh' => ['value' => $capacityWh, 'resolves_to_spec' => true],
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function batteryMah(int $capacityMah): array
    {
        return [
            'category' => 'Battery',
            'attributes' => [
                'battery_capacity_mah' => ['value' => $capacityMah, 'resolves_to_spec' => true],
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function keyboard(string $layout): array
    {
        return [
            'category' => 'Input',
            'attributes' => [
                'keyboard_layout' => ['value' => $layout, 'resolves_to_spec' => true],
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function camera(string $position, string $role, ?float $megapixels, array $details = []): array
    {
        $attributes = [
            'camera_position' => ['value' => $position],
            'camera_role' => [
                'value' => $role,
                'include_in_component_label' => true,
            ],
        ];

        if ($megapixels !== null) {
            $attributes['camera_megapixels'] = [
                'value' => $megapixels,
                'resolves_to_spec' => true,
                'include_in_component_label' => true,
            ];
        }

        foreach ($details as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $attributes[$key] = ['value' => $value];
        }

        return [
            'category' => 'Camera',
            'spec_display_label' => $this->cameraSpecDisplayLabel($role, $megapixels),
            'attributes' => $attributes,
        ];
    }

    private function cameraSpecDisplayLabel(string $role, ?float $megapixels): ?string
    {
        if ($megapixels === null) {
            return null;
        }

        $roleLabel = match ($role) {
            'selfie' => 'Selfie',
            'main' => 'Main',
            'wide' => 'Wide',
            'ultrawide' => 'Ultrawide',
            'telephoto' => 'Telephoto',
            'macro' => 'Macro',
            'depth' => 'Depth',
            default => ucfirst($role),
        };

        $mpLabel = rtrim(rtrim(number_format($megapixels, 1, '.', ''), '0'), '.');

        return $roleLabel . ' ' . $mpLabel . 'MP';
    }

    /**
     * @return array<string,mixed>
     */
    private function wireless(?string $wifiStandard = null, array $details = []): array
    {
        $attributes = [];

        if ($wifiStandard !== null) {
            $attributes['wifi_standard_max'] = [
                'value' => $wifiStandard,
                'resolves_to_spec' => true,
                'include_in_component_label' => true,
            ];
        }

        foreach ($details as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $attributes[$key] = [
                'value' => $value,
                'resolves_to_spec' => true,
                'include_in_component_label' => in_array($key, [
                    'wifi_band_2_4ghz',
                    'wifi_band_5ghz',
                    'wifi_band_6ghz',
                    'bluetooth_version',
                    'cellular_generation_max',
                    'nfc',
                ], true),
            ];
        }

        return [
            'category' => 'Network',
            'spec_display_label' => $wifiStandard,
            'attributes' => $attributes,
        ];
    }

    /**
     * @param array<string,mixed> $attributes
     * @return array<string,mixed>
     */
    private function port(string $connectorType, array $attributes = []): array
    {
        $portAttributes = [
            'port_connector_type' => [
                'value' => $connectorType,
                'resolves_to_spec' => true,
                'include_in_component_label' => true,
            ],
        ];

        foreach ($attributes as $key => $value) {
            $portAttributes[$key] = [
                'value' => $value,
                'resolves_to_spec' => false,
                'include_in_component_label' => $this->portAttributeContributesToLabel($key, $value),
            ];
        }

        return [
            'category' => 'Ports',
            'spec_display_label' => $this->portSpecDisplayLabel($connectorType, $attributes),
            'attributes' => $portAttributes,
        ];
    }

    private function portAttributeContributesToLabel(string $key, mixed $value): bool
    {
        if ($value === false || $value === null || $value === '') {
            return false;
        }

        return in_array($key, [
            'usb_standard',
            'displayport_alt_mode',
            'displayport_version',
            'power_delivery',
            'power_delivery_watts',
            'thunderbolt',
            'thunderbolt_version',
            'hdmi_version',
            'ethernet_speed_max',
            'sleep_and_charge',
            'audio_port_role',
        ], true);
    }

    /**
     * @param array<string,mixed> $attributes
     */
    private function portSpecDisplayLabel(string $connectorType, array $attributes): string
    {
        $label = $this->connectorLabel($connectorType);
        $inline = [];
        $capabilities = [];

        if (!empty($attributes['usb_standard'])) {
            $inline[] = $this->usbStandardLabel((string) $attributes['usb_standard']);
        }

        if (!empty($attributes['hdmi_version'])) {
            $inline[] = (string) $attributes['hdmi_version'];
        }

        if (!empty($attributes['ethernet_speed_max'])) {
            $inline[] = $this->ethernetSpeedLabel((string) $attributes['ethernet_speed_max']);
        }

        if (!empty($attributes['audio_port_role'])) {
            $inline[] = $this->audioRoleLabel((string) $attributes['audio_port_role']);
        }

        if (!empty($attributes['thunderbolt_version'])) {
            $capabilities[] = $this->thunderboltVersionLabel((string) $attributes['thunderbolt_version']);
        } elseif (!empty($attributes['thunderbolt'])) {
            $capabilities[] = 'Thunderbolt';
        }

        if (!empty($attributes['displayport_alt_mode'])) {
            $capabilities[] = !empty($attributes['displayport_version'])
                ? 'DP ' . $attributes['displayport_version'] . ' Alt'
                : 'DP Alt';
        }

        if (!empty($attributes['power_delivery'])) {
            $capabilities[] = !empty($attributes['power_delivery_watts'])
                ? 'PD ' . $attributes['power_delivery_watts'] . ' W'
                : 'PD';
        }

        if (!empty($attributes['sleep_and_charge'])) {
            $capabilities[] = 'Sleep/Charge';
        }

        $display = trim($label . ' ' . implode(' ', array_filter($inline)));

        if ($capabilities !== []) {
            $display .= ' (' . implode(', ', $capabilities) . ')';
        }

        return $display;
    }

    private function connectorLabel(string $value): string
    {
        return match ($value) {
            'usb_a' => 'USB-A',
            'usb_c' => 'USB-C',
            'hdmi' => 'HDMI',
            'displayport' => 'DisplayPort',
            'mini_displayport' => 'Mini DisplayPort',
            'vga' => 'VGA',
            'rj45' => 'RJ-45',
            'esata' => 'eSATA',
            'sd_card' => 'SD Card',
            'audio_3_5mm' => '3.5mm',
            'surface_connect' => 'Surface Connect',
            'lightning' => 'Lightning',
            default => $value,
        };
    }

    private function usbStandardLabel(string $value): string
    {
        return match ($value) {
            'usb_2_0' => 'USB 2.0',
            'usb_3_0' => 'USB 3.0',
            'usb_3_1_gen1' => 'USB 3.1 Gen 1',
            'usb_3_1_gen2' => 'USB 3.1 Gen 2',
            'usb_3_2_gen1' => 'USB 3.2 Gen 1',
            'usb4' => 'USB4',
            default => $value,
        };
    }

    private function ethernetSpeedLabel(string $value): string
    {
        return match ($value) {
            '1gbe' => '1GbE',
            '2_5gbe' => '2.5GbE',
            '5gbe' => '5GbE',
            '10gbe' => '10GbE',
            default => $value,
        };
    }

    private function audioRoleLabel(string $value): string
    {
        return match ($value) {
            'headset_combo' => 'headset combo',
            'headphone_out' => 'headphone out',
            'microphone_in' => 'microphone in',
            'line_in' => 'line in',
            'line_out' => 'line out',
            default => $value,
        };
    }

    private function thunderboltVersionLabel(string $value): string
    {
        return match ($value) {
            '1' => 'Thunderbolt 1',
            '2' => 'Thunderbolt 2',
            '3' => 'Thunderbolt 3',
            '4' => 'Thunderbolt 4',
            '5' => 'Thunderbolt 5',
            default => $value,
        };
    }

    /**
     * @return array<string,mixed>
     */
    private function audioPort(string $role, ?string $standard = null): array
    {
        $attributes = ['audio_port_role' => $role];

        if ($standard !== null) {
            $attributes['audio_jack_standard'] = $standard;
        }

        return $this->port('audio_3_5mm', $attributes);
    }

    /**
     * @return array<string,mixed>
     */
    private function ethernetPort(string $maxSpeed): array
    {
        return $this->port('rj45', [
            'ethernet_speed_max' => $maxSpeed,
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private function subcomponent(string $definition, int $qty = 1): array
    {
        return [
            'definition' => $definition,
            'qty' => $qty,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function template(string $definition, int $qty = 1): array
    {
        return [
            'definition' => $definition,
            'qty' => $qty,
        ];
    }

    private function removeComponentBackedModelAttributes(ModelNumber $modelNumber): void
    {
        $modelNumber->unsetRelation('componentTemplates');

        $definitionIds = app(ModelAttributeManager::class)->componentResolvedSpecDefinitionIds($modelNumber);

        if ($definitionIds === []) {
            return;
        }

        $ownedComponentDefinitionIds = $modelNumber->componentTemplates()
            ->with('componentDefinition:id,metadata_json')
            ->get()
            ->filter(function (ModelNumberComponentTemplate $template): bool {
                $metadata = $template->componentDefinition?->metadata_json ?: [];

                return ($metadata['catalog_seed_class'] ?? null) === self::class;
            })
            ->pluck('component_definition_id')
            ->all();
        $ownedDefinitionIds = ComponentDefinitionAttribute::query()
            ->whereIn('component_definition_id', $ownedComponentDefinitionIds)
            ->where('resolves_to_spec', true)
            ->pluck('attribute_definition_id')
            ->all();
        $catalogAttributeKeys = collect($this->componentDefinitions())
            ->flatMap(fn (array $config): array => array_keys($config['attributes'] ?? []))
            ->unique()
            ->all();
        $catalogDefinitionIds = AttributeDefinition::query()
            ->whereIn('key', $catalogAttributeKeys)
            ->pluck('id')
            ->all();
        $definitionIds = array_values(array_intersect(
            $definitionIds,
            $ownedDefinitionIds,
            $catalogDefinitionIds
        ));

        if ($definitionIds === []) {
            return;
        }

        ModelNumberAttribute::query()
            ->where('model_number_id', $modelNumber->id)
            ->whereIn('attribute_definition_id', $definitionIds)
            ->delete();
    }

    /**
     * @return array<string,string>
     */
    private function seedMetadata(string $seedKey): array
    {
        return [
            'catalog_seed_class' => self::class,
            'catalog_seed_key' => $seedKey,
        ];
    }
}
