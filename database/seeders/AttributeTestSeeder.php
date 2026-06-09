<?php

namespace Database\Seeders;

use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\ComponentDefinition;
use App\Models\TestType;
use App\Models\WorkflowProfile;
use App\Models\WorkflowProfileItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AttributeTestSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedAttributeTests();
        });
    }

    protected function seedAttributeTests(): void
    {
        $definitions = AttributeDefinition::whereIn('key', collect($this->tests())->pluck('attribute')->filter()->all())
            ->get()
            ->keyBy('key');
        $diagnosticItems = [];

        foreach (array_values(array_keys($this->tests())) as $index => $key) {
            $config = $this->tests()[$key];
            /** @var AttributeDefinition|null $definition */
            $definition = filled($config['attribute'] ?? null)
                ? $definitions->get($config['attribute'])
                : null;

            $testType = TestType::updateOrCreate(
                ['slug' => Arr::get($config, 'slug', $key)],
                [
                    'name' => Arr::get($config, 'name', $definition?->label ?? Str::headline($key)),
                    'attribute_definition_id' => $definition?->id,
                    'tooltip' => Arr::get($config, 'tooltip'),
                    'instructions' => Arr::get($config, 'instructions'),
                    'applies_to_all' => (bool) Arr::get($config, 'applies_to_all', false),
                    'is_required' => true,
                    'result_label_mode' => Arr::get($config, 'result_label_mode', WorkflowProfileItem::LABEL_MODE_PASS_FAIL),
                    'display_order' => Arr::get($config, 'display_order', $index),
                ]
            );

            $this->syncItemCategories($testType, $config['categories'] ?? []);
            $this->syncItemComponentCategories($testType, $config['component_categories'] ?? []);
            $this->syncItemComponentDefinitions(
                $testType,
                $config['component_definitions'] ?? [],
                $config['component_definition_prefixes'] ?? []
            );

            if (Arr::get($config, 'profile', true)) {
                $diagnosticItems[] = $testType;
            }
        }

        $this->seedOperationalWorkflowItems();
        $this->seedWorkflowProfiles($diagnosticItems);
        $this->pruneLegacyWorkflowItems();
    }

    /**
     * @return array<string,array<string,string|null>>
     */
    protected function tests(): array
    {
        return [
            'battery' => [
                'name' => 'Batterij',
                'component_categories' => ['Battery'],
                'instructions' => 'Laad de batterij op tot 100%, haal de lader er af, en draai de extreme test 10 minuten. Als de batterij boven 90% is slaagt de test. (Suggestie powercfg /batteryreport /output battery.html)',
            ],
            'bluetooth' => [
                'name' => 'Bluetooth',
                'component_definitions' => ['Wireless', 'Wireless - Generic', 'Bluetooth - Generic'],
                'instructions' => 'Pair een bluetooth apparaat.',
            ],
            'cpu' => [
                'name' => 'Processor',
                'categories' => ['Laptops'],
                'instructions' => 'Draai Prime95 10 minuten op de mode Small FFTs, als er geen errors, crashes, het systeem responsief blijft slaagt de test.',
            ],
            'front_camera' => [
                'name' => 'Selfiecamera',
                'component_definition_prefixes' => ['Camera - Selfie'],
                'instructions' => 'Maak een selfie en controleer autofocus, belichting en eventuele vlekken op de lens.',
            ],
            'rear_camera' => [
                'name' => 'Hoofdcamera',
                'component_definition_prefixes' => ['Camera - Main', 'Camera - Ultrawide', 'Camera - Telephoto'],
                'instructions' => 'Maak meerdere foto\'s met de hoofdcamera en controleer scherpstelling en flitser.',
            ],
            'display' => [
                'name' => 'Scherm',
                'component_categories' => ['Display'],
                'instructions' => 'Ga naar https://www.eizo.be/monitor-test/ en draai de test op full screen, zet op max brightness, met spatie haal je de tekst weg. navigeer door de tests heen en let op kleine witte vlekken, krassen, oneffenheden dit moeten er 0 zijn, maak anders een foto.',
            ],
            'ethernet' => [
                'name' => 'Ethernet',
                'component_definition_prefixes' => ['RJ-45 Ethernet Port'],
                'instructions' => 'Verbind een ethernet kabel en zorg dat het internet nog werkt zonder wifi.',
            ],
            'face_unlock' => [
                'name' => 'Gezichtsherkenning',
                'profile' => false,
                'instructions' => 'Enroll a face and confirm multiple unlock attempts succeed without error.',
            ],
            'hdmi' => [
                'name' => 'HDMI',
                'component_definition_prefixes' => ['HDMI Port'],
                'instructions' => 'Verbind de hdmi kabel en test of beeld en geluid werkt.',
            ],
            'keyboard' => [
                'name' => 'Toetsenbord',
                'component_definition_prefixes' => ['Keyboard'],
                'instructions' => 'Ga naar https://keyboard-test.space/ en test elke toets, voer de test 2x uit, alle toetsen moeten soepel werken.',
            ],
            'microphone' => [
                'name' => 'Microfoon',
                'component_definitions' => ['Microphone'],
                'instructions' => 'Neem wat woorden geluid op en speel deze terug.',
            ],
            'ram' => [
                'name' => 'Geheugen',
                'component_categories' => ['Memory'],
                'instructions' => 'Draai y-cruncher stress test 5 minuten. 0 errors en geen abort',
            ],
            'sd_card_reader' => [
                'name' => 'SD-kaartlezer',
                'component_definitions' => ['SD Card Reader'],
                'instructions' => 'Insert an SD card and verify it mounts and transfers data successfully.',
            ],
            'speaker' => [
                'name' => 'Luidspreker',
                'component_definitions' => ['Speaker'],
                'instructions' => 'Play audio through the internal speakers and listen for clarity and balance.',
            ],
            'storage' => [
                'name' => 'Opslag',
                'component_categories' => ['Storage'],
                'instructions' => 'Crystaldisk info portable of smartctl alle SMART moet 100% groen zijn.',
            ],
            'touchpad' => [
                'name' => 'Touchpad',
                'component_definitions' => ['Touchpad'],
                'instructions' => 'Verify cursor movement, tap-to-click, scrolling, and gesture support.',
            ],
            'usb_ports' => [
                'name' => 'USB-poorten',
                'component_definition_prefixes' => ['USB-A Port', 'USB-C Port'],
                'instructions' => 'Insert a USB device into each port and confirm detection and data transfer.',
            ],
            'vga' => [
                'name' => 'VGA',
                'component_definitions' => ['VGA Port'],
                'instructions' => 'Connect an external monitor via VGA and confirm video output is stable.',
            ],
            'webcam' => [
                'name' => 'Webcam',
                'component_definitions' => ['Webcam'],
                'instructions' => 'Open the camera application to verify the webcam feed and focus.',
            ],
            'wifi' => [
                'name' => 'Wifi',
                'component_definitions' => ['Wireless', 'Wireless - Generic'],
                'instructions' => 'Connect to the designated Wi-Fi network and confirm internet access.',
            ],
            'igpu' => [
                'name' => 'iGPU',
                'categories' => ['Laptops'],
                'instructions' => 'Draai GPU-Z 10 minuten, geen artifacts, driver reset, crash.',
            ],
        ];
    }

    /**
     * @param array<int,string> $categoryNames
     */
    private function syncItemCategories(TestType $item, array $categoryNames): void
    {
        if (!Schema::hasTable('category_workflow_item')) {
            return;
        }

        $categoryIds = Category::query()
            ->whereIn('name', $categoryNames)
            ->where('category_type', 'asset')
            ->pluck('id')
            ->all();

        $item->categories()->sync($categoryIds);
    }

    /**
     * @param array<int,string> $categoryNames
     */
    private function syncItemComponentCategories(TestType $item, array $categoryNames): void
    {
        if (!Schema::hasTable('component_category_workflow_item')) {
            return;
        }

        $categoryIds = Category::query()
            ->whereIn('name', $categoryNames)
            ->where('category_type', 'component')
            ->pluck('id')
            ->all();

        $item->componentCategories()->sync($categoryIds);
    }

    /**
     * @param array<int,string> $definitionNames
     * @param array<int,string> $definitionPrefixes
     */
    private function syncItemComponentDefinitions(TestType $item, array $definitionNames, array $definitionPrefixes): void
    {
        if (!Schema::hasTable('component_definition_workflow_item')) {
            return;
        }

        $query = ComponentDefinition::query()
            ->where(function ($query) use ($definitionNames, $definitionPrefixes): void {
                if ($definitionNames !== []) {
                    $query->whereIn('name', $definitionNames);
                }

                foreach ($definitionPrefixes as $prefix) {
                    $query->orWhere('name', 'like', $prefix.'%');
                }
            });

        $definitionIds = ($definitionNames !== [] || $definitionPrefixes !== [])
            ? $query->pluck('id')->all()
            : [];

        $item->componentDefinitions()->sync($definitionIds);
    }

    private function seedOperationalWorkflowItems(): void
    {
        $categoryIds = Category::query()
            ->where('category_type', 'asset')
            ->pluck('id')
            ->all();

        foreach ($this->operationalItems() as $slug => $config) {
            $item = TestType::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $config['name'],
                    'attribute_definition_id' => null,
                    'tooltip' => $config['tooltip'] ?? null,
                    'instructions' => $config['instructions'] ?? null,
                    'applies_to_all' => true,
                    'is_required' => $config['is_required'] ?? true,
                    'result_label_mode' => $config['result_label_mode'] ?? WorkflowProfileItem::LABEL_MODE_PASS_FAIL,
                    'display_order' => $config['display_order'] ?? 0,
                ]
            );

            $item->categories()->sync(
                isset($config['categories'])
                    ? Category::query()
                        ->whereIn('name', $config['categories'])
                        ->where('category_type', 'asset')
                        ->pluck('id')
                        ->all()
                    : $categoryIds
            );
            $this->syncItemComponentCategories($item, []);
            $this->syncItemComponentDefinitions($item, [], []);
        }
    }

    /**
     * @param array<int, TestType> $diagnosticItems
     */
    private function seedWorkflowProfiles(array $diagnosticItems): void
    {
        $profiles = [
            'standard-diagnostics' => [
                'name' => 'Standard Diagnostics',
                'description' => 'Default diagnostic checks for normal refurbishment.',
                'is_default' => true,
                'blocks_sale_readiness' => true,
                'items' => collect($diagnosticItems)
                    ->sortBy('display_order')
                    ->pluck('slug')
                    ->values()
                    ->all(),
            ],
            'pre-sale-check' => [
                'name' => 'Pre-Sale Check',
                'description' => 'Final visual and evidence checks before sale.',
                'is_default' => false,
                'blocks_sale_readiness' => true,
                'items' => [
                    'case',
                    'quality-grade-confirmed',
                    'sale-photos-present',
                ],
            ],
            'cleaning' => [
                'name' => 'Cleaning',
                'description' => 'Cleaning workflow steps.',
                'is_default' => false,
                'blocks_sale_readiness' => false,
                'label_mode' => WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE,
                'items' => [
                    'cleaning-external',
                    'cleaning-internal',
                ],
            ],
            'shipping-laptop' => [
                'name' => 'Shipping Laptop',
                'description' => 'Laptop packing and shipping preparation.',
                'is_default' => false,
                'blocks_sale_readiness' => false,
                'label_mode' => WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE,
                'items' => [
                    'box-laptop',
                    'add-papers',
                    'seal-and-label-box',
                ],
            ],
        ];

        foreach ($profiles as $slug => $config) {
            $profile = WorkflowProfile::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $config['name'],
                    'description' => $config['description'],
                    'is_active' => true,
                    'is_default' => (bool) $config['is_default'],
                    'blocks_sale_readiness' => (bool) $config['blocks_sale_readiness'],
                    'display_order' => array_search($slug, array_keys($profiles), true) ?: 0,
                ]
            );

            if ($profile->is_default) {
                WorkflowProfile::query()
                    ->whereKeyNot($profile->id)
                    ->update(['is_default' => false]);
            }

            $items = TestType::query()
                ->whereIn('slug', $config['items'])
                ->get()
                ->keyBy('slug');
            $intendedItemIds = $items->pluck('id')->all();

            $staleItemsQuery = WorkflowProfileItem::query()
                ->where('workflow_profile_id', $profile->id);

            if ($intendedItemIds === []) {
                $staleItemsQuery->delete();
            } else {
                $staleItemsQuery
                    ->whereNotIn('workflow_item_id', $intendedItemIds)
                    ->delete();
            }

            foreach ($config['items'] as $index => $itemSlug) {
                $item = $items->get($itemSlug);

                if (!$item) {
                    continue;
                }

                WorkflowProfileItem::updateOrCreate(
                    [
                        'workflow_profile_id' => $profile->id,
                        'workflow_item_id' => $item->id,
                    ],
                    [
                        'sort_order' => $index,
                        'is_required' => $item->is_required,
                        'result_label_mode' => $config['label_mode'] ?? $item->result_label_mode ?? WorkflowProfileItem::LABEL_MODE_PASS_FAIL,
                    ]
                );
            }
        }
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function operationalItems(): array
    {
        return [
            'cleaning-external' => [
                'name' => 'Cleaning - external',
                'instructions' => 'Wipe down exterior surfaces and remove visible residue.',
                'result_label_mode' => WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE,
                'display_order' => 200,
            ],
            'cleaning-internal' => [
                'name' => 'Cleaning - internal',
                'instructions' => 'Clean internal components and remove dust where applicable.',
                'result_label_mode' => WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE,
                'display_order' => 210,
            ],
            'case' => [
                'name' => 'Behuizing',
                'instructions' => 'Controleer behuizing, scharnieren, rubbers, kapjes en zichtbare schade.',
                'display_order' => 300,
            ],
            'quality-grade-confirmed' => [
                'name' => 'Kwaliteit bevestigd',
                'instructions' => 'Controleer dat de kwaliteitsklasse op het asset klopt met de zichtbare staat.',
                'display_order' => 310,
            ],
            'sale-photos-present' => [
                'name' => 'Verkoopfoto\'s aanwezig',
                'instructions' => 'Controleer dat bruikbare verkoopfoto\'s aanwezig zijn of eerder uit workflowfoto\'s zijn gepromoveerd.',
                'result_label_mode' => WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE,
                'display_order' => 320,
            ],
            'box-laptop' => [
                'name' => 'Laptop in doos plaatsen',
                'categories' => ['Laptops'],
                'instructions' => 'Plaats de laptop volgens de verpakkingsinstructies in de doos.',
                'result_label_mode' => WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE,
                'display_order' => 400,
            ],
            'add-papers' => [
                'name' => 'Papieren toevoegen',
                'categories' => ['Laptops'],
                'instructions' => 'Voeg de vereiste papieren en accessoires toe.',
                'result_label_mode' => WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE,
                'display_order' => 410,
            ],
            'seal-and-label-box' => [
                'name' => 'Doos sluiten en labelen',
                'categories' => ['Laptops'],
                'instructions' => 'Sluit de doos en breng het juiste verzendlabel aan.',
                'result_label_mode' => WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE,
                'display_order' => 420,
            ],
        ];
    }

    private function pruneLegacyWorkflowItems(): void
    {
        TestType::query()
            ->whereIn('slug', [
                'install-update-windows',
                'wipen',
            ])
            ->delete();
    }
}
