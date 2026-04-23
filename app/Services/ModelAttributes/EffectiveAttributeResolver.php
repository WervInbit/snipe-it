<?php

namespace App\Services\ModelAttributes;

use App\Models\Asset;
use App\Models\AssetAttributeOverride;
use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\ModelNumber;
use App\Models\ModelNumberAttribute;
use App\Services\Components\AssetComponentRosterService;
use Illuminate\Support\Collection;

class EffectiveAttributeResolver
{
    public function __construct(
        private readonly ComponentAttributeAggregator $componentAggregator,
        private readonly AssetComponentRosterService $rosterService
    ) {
    }

    public function resolveForModel(AssetModel $model): Collection
    {
        $model->loadMissing('primaryModelNumber');

        $modelNumber = $model->primaryModelNumber;

        if (!$modelNumber) {
            return collect();
        }

        return $this->resolveForModelNumber($modelNumber);
    }

    public function resolveForModelNumber(ModelNumber $modelNumber): Collection
    {
        $modelNumber->loadMissing([
            'model.category',
            'attributes.definition.options',
            'attributes.option',
            'componentTemplates.componentDefinition.attributeContributions.definition.options',
            'componentTemplates.componentDefinition.attributeContributions.option',
        ]);

        $manualAssignments = $modelNumber->attributes->keyBy('attribute_definition_id');
        $derivedByDefinition = $this->componentAggregator->aggregateExpectedTemplates($modelNumber->componentTemplates, true);

        $orderedIds = $this->orderedDefinitionIds(
            $modelNumber->attributes->pluck('attribute_definition_id')->map(fn ($id) => (int) $id)->all(),
            $derivedByDefinition->map(fn (ComponentAttributeAggregate $aggregate) => $aggregate->definition)
        );

        return collect($orderedIds)->map(function (int $definitionId) use ($manualAssignments, $derivedByDefinition) {
            /** @var ModelNumberAttribute|null $manualAssignment */
            $manualAssignment = $manualAssignments->get($definitionId);
            /** @var ComponentAttributeAggregate|null $derived */
            $derived = $derivedByDefinition->get($definitionId);

            $definition = $manualAssignment?->definition ?? $derived?->definition;
            if (!$definition) {
                return null;
            }

            return $this->createModelResolved(
                $definition,
                $manualAssignment,
                $derived
            );
        })->filter()->values();
    }

    public function resolveForAsset(Asset $asset, ?ModelNumber $overrideModelNumber = null): Collection
    {
        $asset->loadMissing([
            'model.primaryModelNumber',
            'modelNumber.componentTemplates.componentDefinition.attributeContributions.definition.options',
            'modelNumber.componentTemplates.componentDefinition.attributeContributions.option',
            'attributeOverrides.definition.options',
            'attributeOverrides.option',
            'trackedComponents.componentDefinition.attributeContributions.definition.options',
            'trackedComponents.componentDefinition.attributeContributions.option',
            'expectedComponentStates',
        ]);

        $model = $asset->model;

        $modelNumber = $overrideModelNumber
            ?? $asset->modelNumber
            ?? $model?->primaryModelNumber;

        $overrides = $asset->attributeOverrides->keyBy('attribute_definition_id');
        $roster = $this->rosterService->buildForAsset($asset, $modelNumber);
        $calculatedByDefinition = $this->componentAggregator->aggregateRosterRows($roster->rows);
        $baselineByDefinition = $modelNumber
            ? $this->componentAggregator->aggregateExpectedTemplates($modelNumber->componentTemplates, true)
            : collect();
        $modelResolved = $modelNumber ? $this->resolveForModelNumber($modelNumber) : collect();
        $modelResolvedByDefinition = $modelResolved->keyBy(fn (ResolvedAttribute $resolved) => $resolved->definition->id);

        if (!$modelNumber && $overrides->isEmpty() && $calculatedByDefinition->isEmpty()) {
            return collect();
        }

        $orderedIds = $this->orderedDefinitionIds(
            $modelResolved->map(fn (ResolvedAttribute $resolved) => $resolved->definition->id)->all(),
            $baselineByDefinition->map(fn (ComponentAttributeAggregate $aggregate) => $aggregate->definition),
            $calculatedByDefinition->map(fn (ComponentAttributeAggregate $aggregate) => $aggregate->definition),
            $overrides->map(fn (AssetAttributeOverride $override) => $override->definition)
        );

        return collect($orderedIds)->map(function (int $definitionId) use ($overrides, $calculatedByDefinition, $baselineByDefinition, $modelResolvedByDefinition) {
            /** @var ResolvedAttribute|null $modelResolved */
            $modelResolved = $modelResolvedByDefinition->get($definitionId);
            /** @var AssetAttributeOverride|null $override */
            $override = $overrides->get($definitionId);
            /** @var ComponentAttributeAggregate|null $calculated */
            $calculated = $calculatedByDefinition->get($definitionId);
            /** @var ComponentAttributeAggregate|null $baseline */
            $baseline = $baselineByDefinition->get($definitionId);

            $definition = $modelResolved?->definition
                ?? $baseline?->definition
                ?? $calculated?->definition
                ?? $override?->definition;

            if (!$definition) {
                return null;
            }

            if (!$calculated && $baseline && $definition->isNumericDatatype() && ($baseline->meta['resolves_to_spec'] ?? false)) {
                $calculated = $this->componentAggregator->zeroAggregate($definition, 'calculated_components', [
                    'expected_component_baseline_value' => $baseline->value,
                    'current_component_value' => '0',
                    'reduced_expected_baseline' => true,
                ]);
            }

            return $this->createAssetResolved(
                $definition,
                $modelResolved,
                $override,
                $calculated,
                $baseline
            );
        })->filter()->values();
    }

    protected function requiresTest(AttributeDefinition $definition): bool
    {
        if ($definition->relationLoaded('tests')) {
            return $definition->tests->isNotEmpty();
        }

        return $definition->tests()->exists();
    }

    public function createResolved(AttributeDefinition $definition, ?ModelNumberAttribute $modelValue = null, ?AssetAttributeOverride $override = null, bool $isOverride = false): ResolvedAttribute
    {
        $modelValueString = $modelValue?->value;
        $modelRaw = $modelValue?->raw_value;
        $requiresTest = $this->requiresTest($definition);

        if ($override && $isOverride) {
            $override->loadMissing('option');

            return new ResolvedAttribute(
                $definition,
                $override->value,
                $override->raw_value,
                $override->option,
                'override',
                $requiresTest,
                true,
                $modelValueString,
                $modelRaw,
                $modelValueString,
                $modelRaw,
                $this->buildProvenance(
                    $modelValue ? [[
                        'source' => 'model',
                        'contributors' => [[
                            'label' => __('Manual model value'),
                            'value' => $modelValueString,
                        ]],
                    ]] : [],
                    [[
                        'source' => 'override',
                        'contributors' => [[
                            'label' => __('Asset override'),
                            'value' => $override->value,
                        ]],
                    ]]
                )
            );
        }

        if ($modelValue) {
            $modelValue->loadMissing('option');

            return new ResolvedAttribute(
                $definition,
                $modelValue->value,
                $modelValue->raw_value,
                $modelValue->option,
                'model',
                $requiresTest,
                false,
                $modelValueString,
                $modelRaw,
                $modelValueString,
                $modelRaw,
                $this->buildProvenance([[
                    'source' => 'model',
                    'contributors' => [[
                        'label' => __('Manual model value'),
                        'value' => $modelValueString,
                    ]],
                ]])
            );
        }

        return new ResolvedAttribute(
            $definition,
            null,
            null,
            null,
            'missing',
            $requiresTest,
            false,
            $modelValueString,
            $modelRaw,
            null,
            null,
            []
        );
    }

    private function createModelResolved(
        AttributeDefinition $definition,
        ?ModelNumberAttribute $manualAssignment,
        ?ComponentAttributeAggregate $derived
    ): ResolvedAttribute {
        $manualAssignment?->loadMissing('option');
        $requiresTest = $this->requiresTest($definition);

        $manualValue = $manualAssignment?->value;
        $manualRaw = $manualAssignment?->raw_value;
        $manualOption = $manualAssignment?->option;
        $isCalculatedNumeric = $derived
            && $definition->isNumericDatatype()
            && ($derived->meta['resolves_to_spec'] ?? false);

        $effectiveValue = $isCalculatedNumeric ? $derived->value : ($manualValue ?? $derived?->value);
        $effectiveRaw = $isCalculatedNumeric ? $derived->rawValue : ($manualRaw ?? $derived?->rawValue);
        $effectiveOption = $isCalculatedNumeric ? $derived->option : ($manualOption ?? $derived?->option);
        $source = $isCalculatedNumeric
            ? 'calculated_components'
            : ($manualValue !== null
                ? 'model'
                : ($derived ? 'expected_components' : 'missing'));

        return new ResolvedAttribute(
            $definition,
            $effectiveValue,
            $effectiveRaw,
            $effectiveOption,
            $source,
            $requiresTest,
            false,
            $effectiveValue,
            $effectiveRaw,
            $manualValue,
            $manualRaw,
            $this->buildProvenance(
                $manualValue !== null ? [[
                    'source' => 'model',
                    'contributors' => [[
                        'label' => __('Manual model value'),
                        'value' => $manualValue,
                    ]],
                ]] : [],
                $derived ? [[
                    'source' => $isCalculatedNumeric ? 'calculated_components' : 'expected_components',
                    'contributors' => $derived->contributors,
                ]] : []
            ),
            [
                'expected_component_baseline_value' => $derived?->value,
                'current_component_value' => $derived?->value,
                'reduced_expected_baseline' => false,
            ]
        );
    }

    private function createAssetResolved(
        AttributeDefinition $definition,
        ?ResolvedAttribute $modelResolved,
        ?AssetAttributeOverride $override,
        ?ComponentAttributeAggregate $calculated = null,
        ?ComponentAttributeAggregate $baseline = null
    ): ResolvedAttribute {
        $override?->loadMissing('option');
        $requiresTest = $this->requiresTest($definition);
        $modelValue = $modelResolved?->modelValue;
        $modelRaw = $modelResolved?->modelRawValue;

        $source = 'missing';
        $value = null;
        $rawValue = null;
        $option = null;
        $isOverride = false;
        $meta = [];

        if ($calculated && $definition->isNumericDatatype()) {
            $source = 'calculated_components';
            $value = $calculated->value;
            $rawValue = $calculated->rawValue;
            $option = $calculated->option;
            $baselineValue = $baseline?->value ?? $modelResolved?->value;
            $meta = [
                'expected_component_baseline_value' => $baselineValue,
                'current_component_value' => $calculated->value,
                'reduced_expected_baseline' => $baselineValue !== null
                    && $this->numericStringToFloat($calculated->value) < $this->numericStringToFloat($baselineValue),
            ];
        } elseif ($override && $definition->allow_asset_override) {
            $source = 'override';
            $value = $override->value;
            $rawValue = $override->raw_value;
            $option = $override->option;
            $isOverride = true;
        } elseif ($modelResolved && $modelResolved->source !== 'missing') {
            $source = $modelResolved->source;
            $value = $modelResolved->value;
            $rawValue = $modelResolved->rawValue;
            $option = $modelResolved->option;
            $meta = $modelResolved->meta;
        }

        return new ResolvedAttribute(
            $definition,
            $value,
            $rawValue,
            $option,
            $source,
            $requiresTest,
            $isOverride,
            $modelValue,
            $modelRaw,
            $modelResolved?->manualModelValue,
            $modelResolved?->manualModelRawValue,
            $this->buildProvenance(
                $modelResolved?->provenance ?? [],
                $calculated ? [[
                    'source' => 'calculated_components',
                    'contributors' => $calculated->contributors,
                ]] : [],
                (!$calculated && $override) ? [[
                    'source' => 'override',
                    'contributors' => [[
                        'label' => __('Asset override'),
                        'value' => $override->value,
                    ]],
                ]] : []
            ),
            $meta
        );
    }

    /**
     * @param array<int, int> $preferredIds
     */
    private function orderedDefinitionIds(array $preferredIds, Collection ...$definitionCollections): array
    {
        $ordered = collect($preferredIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $extraDefinitions = collect();

        foreach ($definitionCollections as $definitions) {
            $extraDefinitions = $extraDefinitions->merge($definitions->filter());
        }

        $extraIds = $extraDefinitions
            ->filter(fn ($definition) => $definition instanceof AttributeDefinition)
            ->sortBy(fn (AttributeDefinition $definition) => mb_strtolower($definition->label ?: $definition->key))
            ->map(fn (AttributeDefinition $definition) => (int) $definition->id)
            ->filter(fn (int $id) => !$ordered->contains($id))
            ->values();

        return $ordered->merge($extraIds)->unique()->values()->all();
    }

    /**
     * @param array<int, array<string, mixed>> ...$groups
     * @return array<int, array<string, mixed>>
     */
    private function buildProvenance(array ...$groups): array
    {
        return collect($groups)
            ->flatten(1)
            ->filter(fn ($item) => is_array($item) && !empty($item['source']))
            ->values()
            ->all();
    }

    private function numericStringToFloat(?string $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) $value;
    }
}
