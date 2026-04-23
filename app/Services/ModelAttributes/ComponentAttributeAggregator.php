<?php

namespace App\Services\ModelAttributes;

use App\Models\AttributeDefinition;
use App\Models\AttributeOption;
use App\Models\ComponentDefinitionAttribute;
use App\Models\ComponentInstance;
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
        ]);

        $records = collect();

        foreach ($templates as $template) {
            if (!$template instanceof ModelNumberComponentTemplate || !$template->componentDefinition) {
                continue;
            }

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
                    'component_definition_id' => $template->component_definition_id,
                    'model_number_component_template_id' => $template->id,
                    'slot_name' => $template->slot_name,
                    'resolves_to_spec' => (bool) $contribution->resolves_to_spec,
                ]);
            }
        }

        return $this->aggregateRecords($records, 'expected_components');
    }

    public function aggregateInstalledComponents(Collection $components, bool $specOnly = false): Collection
    {
        if ($components->isEmpty()) {
            return collect();
        }

        $components->loadMissing([
            'componentDefinition.attributeContributions.definition.options',
            'componentDefinition.attributeContributions.option',
        ]);

        $records = collect();

        foreach ($components as $component) {
            if (!$component instanceof ComponentInstance || !$component->componentDefinition) {
                continue;
            }

            foreach ($component->componentDefinition->attributeContributions as $contribution) {
                if (!$this->shouldAggregateContribution($contribution, $specOnly)) {
                    continue;
                }

                $records->push([
                    'definition' => $contribution->definition,
                    'value' => $contribution->value,
                    'raw_value' => $contribution->raw_value,
                    'option' => $contribution->option,
                    'quantity' => 1,
                    'label' => $component->display_name ?: $component->component_tag,
                    'component_definition_id' => $component->component_definition_id,
                    'component_instance_id' => $component->id,
                    'component_tag' => $component->component_tag,
                    'installed_as' => $component->installed_as,
                    'resolves_to_spec' => (bool) $contribution->resolves_to_spec,
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

        foreach ($rows as $row) {
            if (!$row instanceof AssetComponentRosterRow) {
                continue;
            }

            $contributions = $row->component?->componentDefinition?->attributeContributions
                ?? $row->template?->componentDefinition?->attributeContributions
                ?? collect();

            foreach ($contributions as $contribution) {
                if (!$this->shouldAggregateContribution($contribution, true)) {
                    continue;
                }

                $records->push([
                    'definition' => $contribution->definition,
                    'value' => $contribution->value,
                    'raw_value' => $contribution->raw_value,
                    'option' => $contribution->option,
                    'quantity' => 1,
                    'label' => $row->name,
                    'component_definition_id' => $row->component?->component_definition_id ?? $row->template?->component_definition_id,
                    'component_instance_id' => $row->component?->id,
                    'component_tag' => $row->component?->component_tag,
                    'installed_as' => $row->installedAs,
                    'model_number_component_template_id' => $row->template?->id,
                    'classification' => $row->classification,
                    'resolves_to_spec' => true,
                ]);
            }
        }

        return $this->aggregateRecords($records, 'calculated_components');
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

    private function aggregateRecords(Collection $records, string $source): Collection
    {
        return $records
            ->groupBy(fn (array $record) => $record['definition']->id)
            ->map(function (Collection $group) use ($source): ?ComponentAttributeAggregate {
                $definition = $group->first()['definition'] ?? null;

                if (!$definition instanceof AttributeDefinition) {
                    return null;
                }

                [$value, $rawValue, $option] = match ($definition->datatype) {
                    AttributeDefinition::DATATYPE_INT => $this->aggregateInteger($group),
                    AttributeDefinition::DATATYPE_DECIMAL => $this->aggregateDecimal($group),
                    AttributeDefinition::DATATYPE_BOOL => $this->aggregateBoolean($group),
                    AttributeDefinition::DATATYPE_ENUM,
                    AttributeDefinition::DATATYPE_TEXT => $this->aggregateDistinctStrings($group),
                    default => [null, null, null],
                };

                if ($value === null) {
                    return null;
                }

                return new ComponentAttributeAggregate(
                    $definition,
                    $value,
                    $rawValue,
                    $option,
                    $source,
                    $group->map(function (array $record): array {
                        return [
                            'label' => $record['label'] ?? null,
                            'value' => $record['value'] ?? null,
                            'raw_value' => $record['raw_value'] ?? null,
                            'quantity' => $record['quantity'] ?? 1,
                            'component_definition_id' => $record['component_definition_id'] ?? null,
                            'component_instance_id' => $record['component_instance_id'] ?? null,
                            'model_number_component_template_id' => $record['model_number_component_template_id'] ?? null,
                            'slot_name' => $record['slot_name'] ?? null,
                            'component_tag' => $record['component_tag'] ?? null,
                            'installed_as' => $record['installed_as'] ?? null,
                            'classification' => $record['classification'] ?? null,
                        ];
                    })->values()->all(),
                    [
                        'resolves_to_spec' => $group->contains(fn (array $record) => !empty($record['resolves_to_spec'])),
                    ]
                );
            })
            ->filter()
            ->mapWithKeys(fn (ComponentAttributeAggregate $aggregate) => [$aggregate->definition->id => $aggregate]);
    }

    private function shouldAggregateContribution(ComponentDefinitionAttribute $contribution, bool $specOnly): bool
    {
        if (!$contribution->definition) {
            return false;
        }

        if (!$specOnly) {
            return true;
        }

        return $contribution->resolves_to_spec
            && $contribution->definition->isNumericDatatype();
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
     * @return array{0: ?string, 1: ?string, 2: ?AttributeOption}
     */
    private function aggregateDistinctStrings(Collection $group): array
    {
        $distinctValues = [];
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
            $optionsByKey[$key] = $record['option'] instanceof AttributeOption ? $record['option'] : null;
        }

        if ($distinctValues === []) {
            return [null, null, null];
        }

        $values = array_values($distinctValues);
        $value = count($values) === 1 ? $values[0] : implode(', ', $values);
        $firstKey = array_key_first($distinctValues);
        $option = count($values) === 1 ? ($optionsByKey[$firstKey] ?? null) : null;

        return [$value, $value, $option];
    }

    private function trimTrailingZeros(float $value): string
    {
        $normalized = rtrim(rtrim(number_format($value, 10, '.', ''), '0'), '.');

        return $normalized === '' ? '0' : $normalized;
    }
}
