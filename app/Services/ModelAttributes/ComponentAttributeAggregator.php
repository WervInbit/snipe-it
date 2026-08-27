<?php

namespace App\Services\ModelAttributes;

use App\Models\AttributeDefinition;
use App\Models\AttributeOption;
use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionAttribute;
use App\Models\ComponentDefinitionSubcomponentTemplate;
use App\Models\ComponentInstance;
use App\Models\ComponentInstanceAttribute;
use App\Models\ModelNumberComponentTemplate;
use App\Services\Components\AssetComponentRosterRow;
use Illuminate\Support\Collection;

class ComponentAttributeAggregator
{
    public function aggregateExpectedTemplates(Collection $templates, bool $specOnly = false): Collection
    {
        if ($templates->isEmpty()) {
            return collect();
        }

        $templates->loadMissing([
            'componentDefinition.attributeContributions.definition.options',
            'componentDefinition.attributeContributions.option',
            'componentDefinition.subcomponentTemplates.childComponentDefinition.attributeContributions.definition.options',
            'componentDefinition.subcomponentTemplates.childComponentDefinition.attributeContributions.option',
        ]);

        $records = collect();

        foreach ($templates as $template) {
            if (!$template instanceof ModelNumberComponentTemplate || !$template->componentDefinition) {
                continue;
            }

            $parentKey = 'model_template:' . $template->id;
            $componentLabel = $this->componentSpecLabelForDefinition(
                $template->componentDefinition,
                $template->expected_name ?: $template->componentDefinition->name
            );

            foreach ($template->componentDefinition->attributeContributions as $contribution) {
                if (!$this->shouldAggregateContribution($contribution, $specOnly)) {
                    continue;
                }

                $records->push([
                    'definition' => $contribution->definition,
                    'value' => $contribution->value,
                    'raw_value' => $contribution->raw_value,
                    'option' => $contribution->option,
                    'quantity' => max(1, (int) $template->expected_qty),
                    'label' => $template->expected_name,
                    'component_label' => $componentLabel,
                    'component_definition_id' => $template->component_definition_id,
                    'model_number_component_template_id' => $template->id,
                    'slot_name' => $template->slot_name,
                    'hierarchy_record_key' => $parentKey,
                    'resolves_to_spec' => (bool) $contribution->resolves_to_spec,
                ]);
            }

            $this->pushExpectedSubcomponentTemplateRecords(
                $records,
                $template->componentDefinition->subcomponentTemplates ?? collect(),
                $parentKey,
                max(1, (int) $template->expected_qty),
                '',
                $template->id
            );
        }

        [$records, $suppressedRecordsByDefinition] = $this->applyHierarchyPrecedence($records);

        return $this->aggregateRecords($records, 'expected_components', $suppressedRecordsByDefinition);
    }

    public function aggregateInstalledComponents(Collection $components, bool $specOnly = false): Collection
    {
        if ($components->isEmpty()) {
            return collect();
        }

        $components->loadMissing([
            'componentDefinition.attributeContributions.definition.options',
            'componentDefinition.attributeContributions.option',
            'instanceAttributes.definition.options',
            'instanceAttributes.option',
        ]);

        $records = collect();

        foreach ($components as $component) {
            if (!$component instanceof ComponentInstance) {
                continue;
            }

            $componentLabel = $this->componentSpecLabelForComponent($component);

            foreach ($this->effectiveContributionValues($component, $specOnly) as $contribution) {
                $records->push([
                    'definition' => $contribution['definition'],
                    'value' => $contribution['value'],
                    'raw_value' => $contribution['raw_value'],
                    'option' => $contribution['option'],
                    'quantity' => 1,
                    'label' => $component->display_name ?: $component->component_tag,
                    'component_label' => $componentLabel,
                    'component_definition_id' => $component->component_definition_id,
                    'component_instance_id' => $component->id,
                    'parent_component_instance_id' => $component->parent_component_instance_id,
                    'component_tag' => $component->component_tag,
                    'installed_as' => $component->installed_as,
                    'attribute_source' => $contribution['source'],
                    'resolves_to_spec' => $contribution['resolves_to_spec'],
                ]);
            }
        }

        return $this->aggregateRecords($records, 'installed_components');
    }

    public function aggregateRosterRows(Collection $rows): Collection
    {
        if ($rows->isEmpty()) {
            return collect();
        }

        $records = collect();

        foreach ($rows as $rowIndex => $row) {
            if (!$row instanceof AssetComponentRosterRow || $row->isRemoved()) {
                continue;
            }

            $parentKey = $this->rosterRowRecordKey($row, (int) $rowIndex);
            $parentRecordKey = $row->component?->parent_component_instance_id
                ? 'component:' . $row->component->parent_component_instance_id
                : null;
            $contributions = $row->component
                ? $this->effectiveContributionValues($row->component, true)
                : $this->definitionContributionValues($row->template?->componentDefinition?->attributeContributions ?? collect(), true);
            $componentLabel = $row->component
                ? $this->componentSpecLabelForComponent($row->component)
                : $this->componentSpecLabelForDefinition($row->template?->componentDefinition, $row->name);

            foreach ($contributions as $contribution) {
                $records->push([
                    'definition' => $contribution['definition'],
                    'value' => $contribution['value'],
                    'raw_value' => $contribution['raw_value'],
                    'option' => $contribution['option'],
                    'quantity' => $row->component ? 1 : max(1, $row->quantity),
                    'label' => $row->name,
                    'component_label' => $componentLabel,
                    'component_definition_id' => $row->component?->component_definition_id ?? $row->template?->component_definition_id,
                    'component_instance_id' => $row->component?->id,
                    'parent_component_instance_id' => $row->component?->parent_component_instance_id,
                    'component_tag' => $row->component?->component_tag,
                    'installed_as' => $row->installedAs,
                    'model_number_component_template_id' => $row->template?->id,
                    'classification' => $row->classification,
                    'attribute_source' => $contribution['source'],
                    'hierarchy_record_key' => $parentKey,
                    'hierarchy_parent_key' => $parentRecordKey,
                    'resolves_to_spec' => $contribution['resolves_to_spec'],
                ]);
            }

            $this->pushRosterRowExpectedSubcomponentRecords($records, $row, $parentKey);
        }

        [$records, $suppressedRecordsByDefinition] = $this->applyHierarchyPrecedence($records);

        return $this->aggregateRecords($records, 'calculated_components', $suppressedRecordsByDefinition);
    }

    public function zeroAggregate(AttributeDefinition $definition, string $source, array $meta = []): ComponentAttributeAggregate
    {
        return new ComponentAttributeAggregate(
            $definition,
            '0',
            '0',
            null,
            $source,
            [],
            $meta
        );
    }

    private function aggregateRecords(Collection $records, string $source, ?Collection $suppressedRecordsByDefinition = null): Collection
    {
        $suppressedRecordsByDefinition ??= collect();

        return $records
            ->groupBy(fn (array $record) => $record['definition']->id)
            ->map(function (Collection $group) use ($source, $suppressedRecordsByDefinition): ?ComponentAttributeAggregate {
                $definition = $group->first()['definition'] ?? null;

                if (!$definition instanceof AttributeDefinition) {
                    return null;
                }

                [$value, $rawValue, $option, $displayValue] = match ($definition->datatype) {
                    AttributeDefinition::DATATYPE_INT => [...$this->aggregateInteger($group), null],
                    AttributeDefinition::DATATYPE_DECIMAL => [...$this->aggregateDecimal($group), null],
                    AttributeDefinition::DATATYPE_BOOL => [...$this->aggregateBoolean($group), null],
                    AttributeDefinition::DATATYPE_ENUM,
                    AttributeDefinition::DATATYPE_TEXT => $this->aggregateDistinctStrings($group),
                    default => [null, null, null, null],
                };

                if ($value === null) {
                    return null;
                }

                if ($definition->usesComponentLabelsForSpecDisplay()) {
                    $displayValue = $this->aggregateComponentLabels($group);
                }

                $suppressedRecords = $suppressedRecordsByDefinition->get($definition->id, collect());

                return new ComponentAttributeAggregate(
                    $definition,
                    $value,
                    $rawValue,
                    $option,
                    $source,
                    $group->map(function (array $record): array {
                        return [
                            'label' => $record['label'] ?? null,
                            'component_label' => $record['component_label'] ?? null,
                            'value' => $record['value'] ?? null,
                            'raw_value' => $record['raw_value'] ?? null,
                            'quantity' => $record['quantity'] ?? 1,
                            'component_definition_id' => $record['component_definition_id'] ?? null,
                            'component_instance_id' => $record['component_instance_id'] ?? null,
                            'parent_component_instance_id' => $record['parent_component_instance_id'] ?? null,
                            'component_definition_subcomponent_template_id' => $record['component_definition_subcomponent_template_id'] ?? null,
                            'model_number_component_template_id' => $record['model_number_component_template_id'] ?? null,
                            'slot_name' => $record['slot_name'] ?? null,
                            'component_tag' => $record['component_tag'] ?? null,
                            'installed_as' => $record['installed_as'] ?? null,
                            'classification' => $record['classification'] ?? null,
                            'attribute_source' => $record['attribute_source'] ?? null,
                        ];
                    })->values()->all(),
                    [
                        'resolves_to_spec' => $group->contains(fn (array $record) => !empty($record['resolves_to_spec'])),
                        'display_value' => $displayValue,
                        'hierarchy_overlap_warnings' => $this->hierarchyOverlapWarnings($suppressedRecords),
                    ]
                );
            })
            ->filter()
            ->mapWithKeys(fn (ComponentAttributeAggregate $aggregate) => [$aggregate->definition->id => $aggregate]);
    }

    private function shouldAggregateContribution(ComponentDefinitionAttribute|ComponentInstanceAttribute $contribution, bool $specOnly): bool
    {
        if (!$contribution->definition) {
            return false;
        }

        if (!$specOnly) {
            return true;
        }

        return (bool) $contribution->resolves_to_spec;
    }

    private function effectiveContributionValues(ComponentInstance $component, bool $specOnly): Collection
    {
        $component->loadMissing([
            'componentDefinition.attributeContributions.definition.options',
            'componentDefinition.attributeContributions.option',
            'instanceAttributes.definition.options',
            'instanceAttributes.option',
        ]);

        $definitionContributions = $component->componentDefinition?->attributeContributions ?? collect();
        $instanceAttributes = $component->instanceAttributes ?? collect();
        $definitionContributionsByDefinition = $definitionContributions->keyBy('attribute_definition_id');
        $instanceAttributesByDefinition = $instanceAttributes->keyBy('attribute_definition_id');
        $orderedDefinitionIds = $definitionContributions
            ->pluck('attribute_definition_id')
            ->merge($instanceAttributes->pluck('attribute_definition_id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return $orderedDefinitionIds
            ->map(function (int $definitionId) use ($definitionContributionsByDefinition, $instanceAttributesByDefinition, $specOnly): ?array {
                /** @var ComponentInstanceAttribute|null $instanceAttribute */
                $instanceAttribute = $instanceAttributesByDefinition->get($definitionId);
                /** @var ComponentDefinitionAttribute|null $definitionContribution */
                $definitionContribution = $definitionContributionsByDefinition->get($definitionId);
                $contribution = $instanceAttribute ?? $definitionContribution;

                if (!$contribution || !$this->shouldAggregateContribution($contribution, $specOnly)) {
                    return null;
                }

                return [
                    'definition' => $contribution->definition,
                    'value' => $contribution->value,
                    'raw_value' => $contribution->raw_value,
                    'option' => $contribution->option,
                    'resolves_to_spec' => (bool) $contribution->resolves_to_spec,
                    'include_in_component_label' => (bool) ($definitionContribution?->include_in_component_label ?? false),
                    'source' => $instanceAttribute ? 'instance' : 'definition',
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @return array{0: Collection<int, array<string, mixed>>, 1: Collection<int, Collection<int, array<string, mixed>>>}
     */
    private function applyHierarchyPrecedence(Collection $records): array
    {
        if ($records->isEmpty()) {
            return [$records, collect()];
        }

        $kept = collect();
        $suppressed = collect();

        foreach ($records->groupBy(fn (array $record) => $record['definition']->id ?? null) as $group) {
            $childRecordsByParent = $group
                ->filter(fn (array $record) => $this->hierarchyParentKeyForRecord($record) !== null)
                ->groupBy(fn (array $record) => $this->hierarchyParentKeyForRecord($record));

            if ($childRecordsByParent->isEmpty()) {
                $kept = $kept->merge($group);
                continue;
            }

            foreach ($group as $record) {
                $recordKey = $this->hierarchyRecordKeyForRecord($record);
                $childRecords = $recordKey !== null
                    ? $childRecordsByParent->get($recordKey, collect())
                    : collect();

                if ($childRecords->isNotEmpty()) {
                    $suppressed->push(array_merge($record, [
                        'suppressed_by' => $childRecords
                            ->map(fn (array $childRecord) => [
                                'component_instance_id' => $childRecord['component_instance_id'] ?? null,
                                'label' => $childRecord['label'] ?? null,
                                'value' => $childRecord['value'] ?? null,
                                'quantity' => $childRecord['quantity'] ?? 1,
                                'component_tag' => $childRecord['component_tag'] ?? null,
                                'classification' => $childRecord['classification'] ?? null,
                            ])
                            ->values()
                            ->all(),
                    ]));
                    continue;
                }

                $kept->push($record);
            }
        }

        return [
            $kept->values(),
            $suppressed->groupBy(fn (array $record) => $record['definition']->id ?? 0),
        ];
    }

    private function hierarchyOverlapWarnings(Collection $suppressedRecords): array
    {
        return $suppressedRecords
            ->map(fn (array $record) => [
                'label' => $record['label'] ?? null,
                'value' => $record['value'] ?? null,
                'raw_value' => $record['raw_value'] ?? null,
                'component_definition_id' => $record['component_definition_id'] ?? null,
                'component_instance_id' => $record['component_instance_id'] ?? null,
                'component_definition_subcomponent_template_id' => $record['component_definition_subcomponent_template_id'] ?? null,
                'component_tag' => $record['component_tag'] ?? null,
                'installed_as' => $record['installed_as'] ?? null,
                'classification' => $record['classification'] ?? null,
                'attribute_source' => $record['attribute_source'] ?? null,
                'suppressed_by' => $record['suppressed_by'] ?? [],
            ])
            ->values()
            ->all();
    }

    private function rosterRowRecordKey(AssetComponentRosterRow $row, int $rowIndex): string
    {
        if ($row->component) {
            return 'component:' . $row->component->id;
        }

        if ($row->template) {
            return 'model_template:' . $row->template->id . ':row:' . $rowIndex;
        }

        return 'roster_row:' . $rowIndex;
    }

    private function hierarchyRecordKeyForRecord(array $record): ?string
    {
        if (!empty($record['hierarchy_record_key'])) {
            return (string) $record['hierarchy_record_key'];
        }

        if (!empty($record['component_instance_id'])) {
            return 'component:' . $record['component_instance_id'];
        }

        return null;
    }

    private function hierarchyParentKeyForRecord(array $record): ?string
    {
        if (!empty($record['hierarchy_parent_key'])) {
            return (string) $record['hierarchy_parent_key'];
        }

        if (!empty($record['parent_component_instance_id'])) {
            return 'component:' . $record['parent_component_instance_id'];
        }

        return null;
    }

    private function pushRosterRowExpectedSubcomponentRecords(Collection $records, AssetComponentRosterRow $row, string $parentKey): void
    {
        if ($row->isRemoved()) {
            return;
        }

        $parentDefinition = $row->component?->componentDefinition ?? $row->template?->componentDefinition;

        if (!$parentDefinition) {
            return;
        }

        $parentDefinition->loadMissing([
            'subcomponentTemplates.childComponentDefinition.attributeContributions.definition.options',
            'subcomponentTemplates.childComponentDefinition.attributeContributions.option',
        ]);

        $stateByTemplate = $row->component
            ? $row->component->expectedSubcomponentStates->keyBy('component_definition_subcomponent_template_id')
            : collect();

        foreach ($parentDefinition->subcomponentTemplates as $template) {
            $expectedQty = max(1, (int) $template->expected_qty);
            $state = $stateByTemplate->get($template->id);
            $materializedQty = $row->component ? max(0, (int) ($state?->materialized_qty ?? 0)) : 0;
            $removedQty = $row->component ? max(0, (int) ($state?->removed_qty ?? 0)) : 0;
            $remainingQty = max(0, $expectedQty - $materializedQty - $removedQty);

            if ($remainingQty <= 0) {
                continue;
            }

            $this->pushExpectedSubcomponentTemplateRecords(
                $records,
                collect([$template]),
                $parentKey,
                $remainingQty,
                $row->classification,
                $row->template?->id,
                $row->component?->id,
                true
            );
        }
    }

    private function pushExpectedSubcomponentTemplateRecords(
        Collection $records,
        Collection $templates,
        string $parentKey,
        int $quantity,
        string $classification,
        ?int $modelNumberComponentTemplateId = null,
        ?int $parentComponentInstanceId = null,
        bool $quantityIsFinal = false,
    ): void {
        foreach ($templates as $template) {
            if (!$template instanceof ComponentDefinitionSubcomponentTemplate || !$template->childComponentDefinition) {
                continue;
            }

            $childDefinition = $template->childComponentDefinition;
            $recordQuantity = $quantityIsFinal
                ? max(1, $quantity)
                : max(1, $quantity) * max(1, (int) $template->expected_qty);
            $componentLabel = $this->componentSpecLabelForDefinition(
                $childDefinition,
                $template->expected_name ?: $childDefinition->name
            );

            foreach ($this->definitionContributionValues($childDefinition->attributeContributions ?? collect(), true) as $contribution) {
                $records->push([
                    'definition' => $contribution['definition'],
                    'value' => $contribution['value'],
                    'raw_value' => $contribution['raw_value'],
                    'option' => $contribution['option'],
                    'quantity' => $recordQuantity,
                    'label' => $template->expected_name ?: $childDefinition->name,
                    'component_label' => $componentLabel,
                    'component_definition_id' => $childDefinition->id,
                    'component_instance_id' => null,
                    'parent_component_instance_id' => $parentComponentInstanceId,
                    'component_definition_subcomponent_template_id' => $template->id,
                    'model_number_component_template_id' => $modelNumberComponentTemplateId,
                    'classification' => $classification,
                    'attribute_source' => 'expected_subcomponent_template',
                    'hierarchy_record_key' => $parentKey . ':expected_subcomponent_template:' . $template->id,
                    'hierarchy_parent_key' => $parentKey,
                    'resolves_to_spec' => $contribution['resolves_to_spec'],
                ]);
            }
        }
    }

    private function definitionContributionValues(Collection $definitionContributions, bool $specOnly): Collection
    {
        return $definitionContributions
            ->filter(fn ($contribution) => $contribution instanceof ComponentDefinitionAttribute)
            ->filter(fn (ComponentDefinitionAttribute $contribution) => $this->shouldAggregateContribution($contribution, $specOnly))
            ->map(fn (ComponentDefinitionAttribute $contribution) => [
                'definition' => $contribution->definition,
                'value' => $contribution->value,
                'raw_value' => $contribution->raw_value,
                'option' => $contribution->option,
                'resolves_to_spec' => (bool) $contribution->resolves_to_spec,
                'include_in_component_label' => (bool) $contribution->include_in_component_label,
                'source' => 'definition',
            ])
            ->values();
    }

    private function componentSpecLabelForComponent(ComponentInstance $component): string
    {
        $definition = $component->componentDefinition;
        $component->loadMissing([
            'componentDefinition.attributeContributions',
            'instanceAttributes',
        ]);

        if ($definition && trim((string) $definition->spec_display_label) !== '' && !$this->hasLabelContributorInstanceOverride($component)) {
            return trim((string) $definition->spec_display_label);
        }

        return $this->buildComponentSpecLabel(
            $this->effectiveContributionValues($component, false),
            $component->display_name ?: ($component->component_tag ?: $definition?->name)
        );
    }

    private function hasLabelContributorInstanceOverride(ComponentInstance $component): bool
    {
        $labelDefinitionIds = collect($component->componentDefinition?->attributeContributions ?? [])
            ->filter(fn (ComponentDefinitionAttribute $contribution) => (bool) $contribution->include_in_component_label)
            ->pluck('attribute_definition_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($labelDefinitionIds === []) {
            return false;
        }

        return collect($component->instanceAttributes ?? [])
            ->contains(fn (ComponentInstanceAttribute $attribute) => in_array((int) $attribute->attribute_definition_id, $labelDefinitionIds, true));
    }

    private function componentSpecLabelForDefinition(?ComponentDefinition $definition, ?string $fallback = null): string
    {
        if (!$definition) {
            return trim((string) $fallback);
        }

        if (trim((string) $definition->spec_display_label) !== '') {
            return trim((string) $definition->spec_display_label);
        }

        $definition->loadMissing([
            'attributeContributions.definition.options',
            'attributeContributions.option',
        ]);

        return $this->buildComponentSpecLabel(
            $this->definitionContributionValues($definition->attributeContributions ?? collect(), false),
            $fallback ?: $definition->name
        );
    }

    private function buildComponentSpecLabel(Collection $contributions, ?string $fallback): string
    {
        $fragments = $contributions
            ->filter(fn (array $contribution) => !empty($contribution['include_in_component_label']))
            ->map(fn (array $contribution) => $this->displayValueForContribution($contribution, includeUnits: true))
            ->filter(fn (?string $value) => $value !== null && trim($value) !== '')
            ->values();

        if ($fragments->isEmpty()) {
            return trim((string) $fallback);
        }

        return $fragments->implode(' ');
    }

    private function displayValueForContribution(array $contribution, bool $includeUnits = false): ?string
    {
        $definition = $contribution['definition'] ?? null;
        $value = trim((string) ($contribution['value'] ?? ''));

        if ($value === '') {
            return null;
        }

        if (($contribution['option'] ?? null) instanceof AttributeOption) {
            return $contribution['option']->label ?: $value;
        }

        if ($definition instanceof AttributeDefinition && $definition->datatype === AttributeDefinition::DATATYPE_BOOL) {
            return $value === '1' ? $definition->label : null;
        }

        if ($includeUnits && $definition instanceof AttributeDefinition && $definition->unit && $definition->isNumericDatatype()) {
            return trim($value . ' ' . $definition->unit);
        }

        return $value;
    }

    private function aggregateComponentLabels(Collection $group): ?string
    {
        $labels = [];

        foreach ($group as $record) {
            $label = trim((string) ($record['component_label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $key = mb_strtolower($label);
            if (!isset($labels[$key])) {
                $labels[$key] = [
                    'label' => $label,
                    'quantity' => 0,
                ];
            }

            $labels[$key]['quantity'] += max(1, (int) ($record['quantity'] ?? 1));
        }

        if ($labels === []) {
            return null;
        }

        return collect($labels)
            ->map(function (array $item): string {
                $quantity = max(1, (int) $item['quantity']);

                return $quantity > 1
                    ? __(':countx :label', ['count' => $quantity, 'label' => $item['label']])
                    : $item['label'];
            })
            ->values()
            ->implode(', ');
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?AttributeOption}
     */
    private function aggregateInteger(Collection $group): array
    {
        $sum = 0;

        foreach ($group as $record) {
            $value = $record['value'];
            if ($value === null || $value === '') {
                continue;
            }

            $sum += ((int) $value) * max(1, (int) ($record['quantity'] ?? 1));
        }

        return [(string) $sum, (string) $sum, null];
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?AttributeOption}
     */
    private function aggregateDecimal(Collection $group): array
    {
        $sum = 0.0;

        foreach ($group as $record) {
            $value = $record['value'];
            if ($value === null || $value === '') {
                continue;
            }

            $sum += ((float) $value) * max(1, (int) ($record['quantity'] ?? 1));
        }

        $normalized = $this->trimTrailingZeros($sum);

        return [$normalized, $normalized, null];
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?AttributeOption}
     */
    private function aggregateBoolean(Collection $group): array
    {
        if ($group->isEmpty()) {
            return [null, null, null];
        }

        $anyTrue = $group->contains(fn (array $record) => (string) ($record['value'] ?? '') === '1');
        $value = $anyTrue ? '1' : '0';

        return [$value, $value, null];
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?AttributeOption, 3: ?string}
     */
    private function aggregateDistinctStrings(Collection $group): array
    {
        $distinctValues = [];
        $displayValues = [];
        $optionsByKey = [];

        foreach ($group as $record) {
            $value = trim((string) ($record['value'] ?? ''));
            if ($value === '') {
                continue;
            }

            $key = mb_strtolower($value);
            if (array_key_exists($key, $distinctValues)) {
                continue;
            }

            $distinctValues[$key] = $value;
            $displayValues[$key] = $this->displayValueForContribution($record) ?? $value;
            $optionsByKey[$key] = $record['option'] instanceof AttributeOption ? $record['option'] : null;
        }

        if ($distinctValues === []) {
            return [null, null, null, null];
        }

        $values = array_values($distinctValues);
        $value = count($values) === 1 ? $values[0] : implode(', ', $values);
        $display = implode(', ', array_values($displayValues));
        $firstKey = array_key_first($distinctValues);
        $option = count($values) === 1 ? ($optionsByKey[$firstKey] ?? null) : null;

        return [$value, $value, $option, $display !== $value ? $display : null];
    }

    private function trimTrailingZeros(float $value): string
    {
        $normalized = rtrim(rtrim(number_format($value, 10, '.', ''), '0'), '.');

        return $normalized === '' ? '0' : $normalized;
    }
}
