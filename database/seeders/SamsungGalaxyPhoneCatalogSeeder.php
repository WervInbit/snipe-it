<?php

namespace Database\Seeders;

use App\Models\AttributeDefinition;
use App\Models\AssetModel;
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

class SamsungGalaxyPhoneCatalogSeeder extends Seeder
{
    use ProvidesDeviceCatalogData;

    private const WORKFLOW_COMPONENT_PREFIXES = [
        'bluetooth' => ['Wireless'],
        'front_camera' => ['Camera - Selfie'],
        'rear_camera' => ['Camera - Main', 'Camera - Ultrawide', 'Camera - Telephoto', 'Camera - Macro', 'Camera - Depth'],
        'wifi' => ['Wireless'],
    ];

    public function run(): void
    {
        if (!$this->requiredTablesExist()) {
            return;
        }

        $this->call(DeviceAttributeSeeder::class);

        DB::transaction(function (): void {
            $definitions = $this->seedComponentDefinitions();
            $modelNumbers = $this->seedModelNumbers();

            $this->seedModelNumberTemplates($modelNumbers, $definitions);
            $this->seedModelNumberAttributes($modelNumbers);
        });

        $this->syncWorkflowComponentApplicability();
    }

    private function requiredTablesExist(): bool
    {
        foreach ([
            'attribute_definitions',
            'attribute_options',
            'categories',
            'component_definition_attributes',
            'component_definition_subcomponent_templates',
            'component_definitions',
            'manufacturers',
            'model_number_attributes',
            'model_number_component_templates',
            'model_numbers',
            'models',
        ] as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function syncWorkflowComponentApplicability(): void
    {
        if (!Schema::hasTable('component_definition_workflow_item') || !Schema::hasTable('workflow_items')) {
            return;
        }

        foreach (self::WORKFLOW_COMPONENT_PREFIXES as $workflowItemSlug => $prefixes) {
            $workflowItemId = DB::table('workflow_items')->where('slug', $workflowItemSlug)->value('id');

            if (!$workflowItemId) {
                continue;
            }

            $definitionIds = ComponentDefinition::query()
                ->where(function ($query) use ($prefixes): void {
                    foreach ($prefixes as $prefix) {
                        $query->orWhere('name', 'LIKE', $prefix . '%');
                    }
                })
                ->pluck('id')
                ->all();

            foreach ($definitionIds as $definitionId) {
                DB::table('component_definition_workflow_item')->updateOrInsert([
                    'workflow_item_id' => $workflowItemId,
                    'component_definition_id' => $definitionId,
                ]);
            }
        }
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

        $this->seedSubcomponentTemplates($seeded);

        return $seeded;
    }

    /**
     * @param array<string,ComponentDefinition> $seeded
     */
    private function seedSubcomponentTemplates(array $seeded): void
    {
        foreach ($this->componentDefinitions() as $name => $config) {
            $definition = $seeded[$name] ?? null;

            if (!$definition) {
                continue;
            }

            $assignedTemplateIds = [];
            $seedKey = $config['seed_key'] ?? 'samsung.galaxy_phone_catalog.shared';

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
                        $this->seedMetadata($seedKey)
                    ),
                    'notes' => $templateConfig['notes'] ?? null,
                ]);
                $template->save();

                $assignedTemplateIds[] = $template->id;
            }

            $this->deleteSeedOwnedSubcomponentTemplates($definition, $assignedTemplateIds);
        }
    }

    /**
     * @param array<int,int> $assignedTemplateIds
     */
    private function deleteSeedOwnedSubcomponentTemplates(ComponentDefinition $definition, array $assignedTemplateIds): void
    {
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
     * @return array<string,ModelNumber>
     */
    private function seedModelNumbers(): array
    {
        $modelNumbers = [];

        foreach ($this->phoneCatalogEntries() as $entryKey => $entry) {
            $modelConfig = $entry['model'];
            /** @var AssetModel $model */
            $model = $this->catalogAssetModel(
                $modelConfig['name'],
                $modelConfig['category'],
                $modelConfig['manufacturer'],
                $modelConfig['eol'] ?? null
            );

            $numberConfig = $entry['model_number'];
            /** @var ModelNumber $modelNumber */
            $modelNumber = ModelNumber::query()->firstOrNew([
                'model_id' => $model->id,
                'code' => $numberConfig['code'],
            ]);
            $modelNumber->label = $numberConfig['label'];
            $modelNumber->forceFill(['deprecated_at' => null]);
            $modelNumber->save();

            if (!$model->primary_model_number_id) {
                $model->forceFill([
                    'primary_model_number_id' => $modelNumber->id,
                    'model_number' => $modelNumber->code,
                ])->save();
            }

            $modelNumbers[$entryKey] = $modelNumber;
        }

        return $modelNumbers;
    }

    /**
     * @param array<string,ModelNumber> $modelNumbers
     * @param array<string,ComponentDefinition> $definitions
     */
    private function seedModelNumberTemplates(array $modelNumbers, array $definitions): void
    {
        foreach ($this->phoneCatalogEntries() as $entryKey => $entry) {
            $modelNumber = $modelNumbers[$entryKey] ?? null;

            if (!$modelNumber) {
                continue;
            }

            $assignedTemplateIds = [];

            foreach ($entry['templates'] as $index => $templateConfig) {
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
                        $this->seedMetadata($entryKey)
                    ),
                    'notes' => $templateConfig['notes'] ?? null,
                ]);
                $template->save();

                $assignedTemplateIds[] = $template->id;
            }

            $this->deleteSeedOwnedModelTemplates($modelNumber, $assignedTemplateIds);
        }
    }

    /**
     * @param array<int,int> $assignedTemplateIds
     */
    private function deleteSeedOwnedModelTemplates(ModelNumber $modelNumber, array $assignedTemplateIds): void
    {
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
     * @param array<string,ModelNumber> $modelNumbers
     */
    private function seedModelNumberAttributes(array $modelNumbers): void
    {
        $definitions = AttributeDefinition::query()
            ->whereIn('key', array_keys($this->attributeBlueprints()))
            ->get()
            ->keyBy('key');
        $valueService = app(AttributeValueService::class);

        foreach ($this->phoneCatalogEntries() as $entryKey => $entry) {
            $modelNumber = $modelNumbers[$entryKey] ?? null;

            if (!$modelNumber) {
                continue;
            }

            $modelNumber->unsetRelation('componentTemplates');
            $componentBackedDefinitionIds = app(ModelAttributeManager::class)
                ->componentResolvedSpecDefinitionIds($modelNumber);
            $position = 0;

            foreach ($entry['attributes'] as $key => $value) {
                /** @var AttributeDefinition|null $definition */
                $definition = $definitions->get($key);

                if (!$definition) {
                    continue;
                }

                if (in_array((int) $definition->id, $componentBackedDefinitionIds, true)) {
                    continue;
                }

                $tuple = $valueService->validateAndNormalize($definition, $value);

                $assignment = ModelNumberAttribute::firstOrNew([
                    'model_number_id' => $modelNumber->id,
                    'attribute_definition_id' => $definition->id,
                ]);
                $assignment->value = $tuple->value;
                $assignment->raw_value = $tuple->rawValue;
                $assignment->attribute_option_id = $tuple->attributeOptionId;
                $assignment->display_order = $position;
                $assignment->save();

                $position++;
            }

            $this->removeComponentBackedModelAttributes($modelNumber);
        }
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
     * @return array<string,array<string,mixed>>
     */
    private function phoneCatalogEntries(): array
    {
        return [
            'samsung.galaxy_a32.sm_a325f_ds.6gb_128gb.black' => [
                'model' => $this->phoneModel('Samsung Galaxy A32'),
                'model_number' => [
                    'code' => 'SM-A325F/DS-6GB-128GB',
                    'label' => 'Samsung Galaxy A32 - SM-A325F/DS - 6GB - 128GB - Black',
                ],
                'attributes' => [
                    'release_year' => 2021,
                    'ram_size_gb' => 6,
                    'ram_type' => 'lpddr4x',
                    'storage_capacity_gb' => 128,
                    'storage_type' => 'emmc',
                    'display_size_inches' => 6.4,
                    'display_resolution' => '1080 x 2400',
                    'display_panel_type' => 'amoled',
                    'display_refresh_rate_hz' => 90,
                    'battery_capacity' => '5000 mAh',
                    'wifi_band_2_4ghz' => true,
                    'wifi_band_5ghz' => true,
                    'wifi_band_6ghz' => false,
                    'bluetooth_version' => '5.0',
                    'nfc' => true,
                    'cellular_generation_max' => '4g_lte',
                    'supports_5g' => false,
                    'os_family' => 'android',
                    'os_version' => 'Android 11',
                    'color' => 'Awesome Black',
                ],
                'templates' => [
                    $this->template('Logic Board - Samsung Galaxy A32 SM-A325F/DS'),
                    $this->template('Display 6.4 1080x2400 Super AMOLED 90Hz'),
                    $this->template('Battery 5000 mAh'),
                    $this->template('Camera - Selfie - 20MP'),
                    $this->template('Camera - Main - 64MP'),
                    $this->template('Camera - Ultrawide - 8MP'),
                    $this->template('Camera - Macro - 5MP'),
                    $this->template('Camera - Depth - 5MP'),
                    $this->template('Speaker'),
                    $this->template('Microphone'),
                ],
            ],
            'samsung.galaxy_a50.sm_a505fn_ds.4gb_128gb.black' => [
                'model' => $this->phoneModel('Samsung Galaxy A50'),
                'model_number' => [
                    'code' => 'SM-A505FN/DS-4GB-128GB',
                    'label' => 'Samsung Galaxy A50 - SM-A505FN/DS - 4GB - 128GB - Black',
                ],
                'attributes' => [
                    'release_year' => 2019,
                    'ram_size_gb' => 4,
                    'ram_type' => 'lpddr4x',
                    'storage_capacity_gb' => 128,
                    'storage_type' => 'ufs',
                    'display_size_inches' => 6.4,
                    'display_resolution' => '2340 x 1080',
                    'display_panel_type' => 'amoled',
                    'display_refresh_rate_hz' => 60,
                    'battery_capacity' => '4000 mAh',
                    'wifi_band_2_4ghz' => true,
                    'wifi_band_5ghz' => true,
                    'wifi_band_6ghz' => false,
                    'bluetooth_version' => '5.0',
                    'nfc' => true,
                    'cellular_generation_max' => '4g_lte',
                    'supports_5g' => false,
                    'os_family' => 'android',
                    'os_version' => 'Android 9',
                    'color' => 'Black',
                ],
                'templates' => [
                    $this->template('Logic Board - Samsung Galaxy A50 SM-A505FN/DS'),
                    $this->template('Display 6.4 2340x1080 Super AMOLED 60Hz'),
                    $this->template('Battery 4000 mAh'),
                    $this->template('Camera - Selfie - 25MP'),
                    $this->template('Camera - Main - 25MP'),
                    $this->template('Camera - Ultrawide - 8MP'),
                    $this->template('Camera - Depth - 5MP'),
                    $this->template('Speaker'),
                    $this->template('Microphone'),
                ],
            ],
            'samsung.galaxy_a51.sm_a515f_dsn.4gb_128gb.black' => [
                'model' => $this->phoneModel('Samsung Galaxy A51'),
                'model_number' => [
                    'code' => 'SM-A515F/DSN-4GB-128GB',
                    'label' => 'Samsung Galaxy A51 - SM-A515F/DSN - 4GB - 128GB - Black',
                ],
                'attributes' => [
                    'release_year' => 2020,
                    'ram_size_gb' => 4,
                    'ram_type' => 'lpddr4x',
                    'storage_capacity_gb' => 128,
                    'storage_type' => 'ufs',
                    'display_size_inches' => 6.5,
                    'display_resolution' => '1080 x 2400',
                    'display_panel_type' => 'amoled',
                    'display_refresh_rate_hz' => 60,
                    'battery_capacity' => '4000 mAh',
                    'wifi_band_2_4ghz' => true,
                    'wifi_band_5ghz' => true,
                    'wifi_band_6ghz' => false,
                    'bluetooth_version' => '5.0',
                    'nfc' => true,
                    'cellular_generation_max' => '4g_lte',
                    'supports_5g' => false,
                    'os_family' => 'android',
                    'os_version' => 'Android 10',
                    'color' => 'Prism Crush Black',
                ],
                'templates' => [
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
            ],
        ];
    }

    /**
     * @return array<string,string|null>
     */
    private function phoneModel(string $name): array
    {
        return [
            'name' => $name,
            'category' => 'Mobile Phones',
            'manufacturer' => 'Samsung',
            'eol' => '18',
        ];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function componentDefinitions(): array
    {
        return [
            'Logic Board - Samsung Galaxy A32 SM-A325F/DS' => $this->withSeedKey(
                $this->logicBoard([
                    $this->subcomponent('RAM 6GB LPDDR4X'),
                    $this->subcomponent('Storage 128GB eMMC'),
                    $this->subcomponent('Wireless - 802.11ac'),
                    $this->subcomponent('USB-C Charging/Data Port'),
                    $this->subcomponent('3.5mm Port - Headset Combo'),
                ]),
                'samsung.galaxy_a32.sm_a325f_ds.6gb_128gb.black'
            ),
            'Logic Board - Samsung Galaxy A50 SM-A505FN/DS' => $this->withSeedKey(
                $this->logicBoard([
                    $this->subcomponent('RAM 4GB LPDDR4X'),
                    $this->subcomponent('Storage 128GB UFS'),
                    $this->subcomponent('Wireless - 802.11ac'),
                    $this->subcomponent('USB-C Charging/Data Port'),
                    $this->subcomponent('3.5mm Port - Headset Combo'),
                ]),
                'samsung.galaxy_a50.sm_a505fn_ds.4gb_128gb.black'
            ),
            'Logic Board - Samsung Galaxy A51 SM-A515F/DSN' => $this->withSeedKey(
                $this->logicBoard([
                    $this->subcomponent('RAM 4GB LPDDR4X'),
                    $this->subcomponent('Storage 128GB UFS'),
                    $this->subcomponent('Wireless - 802.11ac'),
                    $this->subcomponent('USB-C Charging/Data Port'),
                    $this->subcomponent('3.5mm Port - Headset Combo'),
                ]),
                'samsung.galaxy_a51.sm_a515f_dsn.4gb_128gb.black'
            ),

            'RAM 4GB LPDDR4X' => $this->memory(4, 'lpddr4x'),
            'RAM 6GB LPDDR4X' => $this->memory(6, 'lpddr4x'),
            'Storage 128GB UFS' => $this->storage(128, 'ufs'),
            'Storage 128GB eMMC' => $this->storage(128, 'emmc'),
            'Display 6.4 1080x2400 Super AMOLED 90Hz' => $this->display(6.4, '1080 x 2400', 'amoled', 90),
            'Display 6.4 2340x1080 Super AMOLED 60Hz' => $this->display(6.4, '2340 x 1080', 'amoled', 60),
            'Display 6.5 1080x2400 Super AMOLED 60Hz' => $this->display(6.5, '1080 x 2400', 'amoled', 60),
            'Battery 4000 mAh' => $this->batteryMah(4000),
            'Battery 5000 mAh' => $this->batteryMah(5000),

            'Camera - Selfie - 20MP' => $this->camera('front', 'selfie', 20),
            'Camera - Selfie - 25MP' => $this->camera('front', 'selfie', 25),
            'Camera - Selfie - 32MP' => $this->camera('front', 'selfie', 32),
            'Camera - Main - 25MP' => $this->camera('rear', 'main', 25),
            'Camera - Main - 48MP' => $this->camera('rear', 'main', 48),
            'Camera - Main - 64MP' => $this->camera('rear', 'main', 64),
            'Camera - Ultrawide - 8MP' => $this->camera('rear', 'ultrawide', 8),
            'Camera - Ultrawide - 12MP' => $this->camera('rear', 'ultrawide', 12),
            'Camera - Macro - 5MP' => $this->camera('rear', 'macro', 5),
            'Camera - Depth - 5MP' => $this->camera('rear', 'depth', 5),

            'Speaker' => ['category' => 'Audio'],
            'Microphone' => ['category' => 'Audio'],
            '3.5mm Port - Headset Combo' => $this->audioPort('headset_combo', 'trrs_ctia'),
            'Wireless - 802.11ac' => $this->wireless('802.11ac'),
            'USB-C Charging/Data Port' => $this->port('usb_c'),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function withSeedKey(array $definition, string $seedKey): array
    {
        $definition['seed_key'] = $seedKey;

        return $definition;
    }

    /**
     * @return array<string,mixed>
     */
    private function logicBoard(array $subcomponents): array
    {
        return [
            'category' => 'Logic Board',
            'placement_mode' => ComponentDefinition::PLACEMENT_ASSET_ONLY,
            'subcomponents' => $subcomponents,
        ];
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
    private function display(float $size, string $resolution, string $panel, int $refreshRate): array
    {
        return [
            'category' => 'Display',
            'attributes' => [
                'display_size_inches' => ['value' => $size, 'resolves_to_spec' => true],
                'display_resolution' => ['value' => $resolution, 'resolves_to_spec' => true],
                'display_panel_type' => ['value' => $panel, 'resolves_to_spec' => true],
                'display_refresh_rate_hz' => ['value' => $refreshRate, 'resolves_to_spec' => true],
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
    private function camera(string $position, string $role, float $megapixels): array
    {
        return [
            'category' => 'Camera',
            'spec_display_label' => $this->cameraSpecDisplayLabel($role, $megapixels),
            'attributes' => [
                'camera_position' => ['value' => $position],
                'camera_role' => ['value' => $role, 'include_in_component_label' => true],
                'camera_megapixels' => [
                    'value' => $megapixels,
                    'resolves_to_spec' => true,
                    'include_in_component_label' => true,
                ],
            ],
        ];
    }

    private function cameraSpecDisplayLabel(string $role, float $megapixels): string
    {
        $roleLabel = match ($role) {
            'selfie' => 'Selfie',
            'main' => 'Main',
            'ultrawide' => 'Ultrawide',
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
    private function wireless(string $wifiStandard): array
    {
        return [
            'category' => 'Network',
            'spec_display_label' => $wifiStandard,
            'attributes' => [
                'wifi_standard_max' => [
                    'value' => $wifiStandard,
                    'resolves_to_spec' => true,
                    'include_in_component_label' => true,
                ],
            ],
        ];
    }

    /**
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
                'include_in_component_label' => in_array($key, ['audio_port_role'], true),
            ];
        }

        return [
            'category' => 'Ports',
            'spec_display_label' => $this->portSpecDisplayLabel($connectorType, $attributes),
            'attributes' => $portAttributes,
        ];
    }

    /**
     * @param array<string,mixed> $attributes
     */
    private function portSpecDisplayLabel(string $connectorType, array $attributes): string
    {
        $label = match ($connectorType) {
            'usb_c' => 'USB-C',
            'audio_3_5mm' => '3.5mm',
            default => $connectorType,
        };

        if (($attributes['audio_port_role'] ?? null) === 'headset_combo') {
            return $label . ' headset combo';
        }

        return $label;
    }

    /**
     * @return array<string,mixed>
     */
    private function audioPort(string $role, string $standard): array
    {
        return $this->port('audio_3_5mm', [
            'audio_port_role' => $role,
            'audio_jack_standard' => $standard,
        ]);
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
