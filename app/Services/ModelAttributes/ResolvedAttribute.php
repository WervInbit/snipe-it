<?php

namespace App\Services\ModelAttributes;

use App\Models\AttributeDefinition;
use App\Models\AttributeOption;
use Illuminate\Support\Collection;

class ResolvedAttribute
{
    /**
     * @param array<int, array<string, mixed>> $provenance
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public readonly AttributeDefinition $definition,
        public readonly ?string $value,
        public readonly ?string $rawValue,
        public readonly ?AttributeOption $option,
        public readonly string $source,
        public readonly bool $requiresTest,
        public readonly bool $isOverride,
        public readonly ?string $modelValue,
        public readonly ?string $modelRawValue,
        public readonly ?string $manualModelValue = null,
        public readonly ?string $manualModelRawValue = null,
        public readonly array $provenance = [],
        public readonly array $meta = []
    ) {
    }

    public function formattedValue(): ?string
    {
        return $this->formatValue($this->meta['display_value'] ?? $this->value, $this->option);
    }

    public function formattedModelValue(): ?string
    {
        return $this->formatValue($this->meta['model_display_value'] ?? $this->modelValue);
    }

    public function formattedManualModelValue(): ?string
    {
        return $this->formatValue(
            $this->meta['manual_model_display_value'] ?? $this->manualModelValue,
            $this->optionForValue($this->manualModelValue)
        );
    }

    public function formattedComponentValueForManualConflict(): ?string
    {
        $displayValue = $this->meta['component_resolved_model_display_value']
            ?? $this->meta['current_component_display_value']
            ?? $this->meta['expected_component_baseline_display_value']
            ?? null;

        if ($displayValue !== null) {
            return $this->formatValue((string) $displayValue);
        }

        $componentValue = $this->componentValueForManualConflict();

        return $this->formatValue($componentValue, $this->optionForValue($componentValue));
    }

    public function hasManualModelComponentConflict(): bool
    {
        if (!$this->hasComparableValue($this->manualModelValue) || !$this->componentValueTakesPrecedence()) {
            return false;
        }

        $componentValue = $this->componentValueForManualConflict();

        if (!$this->hasComparableValue($componentValue)) {
            return false;
        }

        return $this->normalizeComparableValue($this->manualModelValue) !== $this->normalizeComparableValue($componentValue);
    }

    public function manualModelComponentConflictMessage(): ?string
    {
        if (!$this->hasManualModelComponentConflict()) {
            return null;
        }

        return __('Manual model value :manual differs from component value :component. Component value is being used.', [
            'manual' => $this->formattedManualModelValue() ?? __('Not specified'),
            'component' => $this->formattedComponentValueForManualConflict() ?? __('Not specified'),
        ]);
    }

    public function formattedCalculatedBaselineValue(): ?string
    {
        return $this->formatValue($this->meta['expected_component_baseline_display_value'] ?? $this->meta['expected_component_baseline_value'] ?? null);
    }

    public function formattedCalculatedExpectedSubtotal(): ?string
    {
        return $this->formatValue($this->calculatedSubtotalFor(['expected', 'expected_tracked']));
    }

    public function formattedCalculatedExtraSubtotal(): ?string
    {
        return $this->formatValue($this->calculatedSubtotalFor(['extra', 'custom']));
    }

    public function calculatedExpectedContributorSummary(): ?string
    {
        return $this->contributorSummaryForClassifications('calculated_components', ['expected', 'expected_tracked']);
    }

    public function calculatedExtraContributorSummary(): ?string
    {
        return $this->contributorSummaryForClassifications('calculated_components', ['extra', 'custom']);
    }

    public function sourceLabel(): string
    {
        return match ($this->source) {
            'override' => __('Asset override'),
            'installed_components' => __('Installed components'),
            'calculated_components' => __('Calculated from components'),
            'model' => __('Manual model value'),
            'expected_components' => __('Expected components'),
            default => __('Missing'),
        };
    }

    public function modelSourceLabel(): string
    {
        if ($this->manualModelValue !== null) {
            return __('Manual model value');
        }

        if ($this->source === 'calculated_components' || !empty($this->meta['expected_component_baseline_value'])) {
            return __('Calculated from components');
        }

        if ($this->modelValue !== null) {
            return __('Expected components');
        }

        return __('Missing');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function contributorsFor(string $source): array
    {
        return array_values(array_filter($this->provenance, fn (array $item) => ($item['source'] ?? null) === $source));
    }

    public function contributorSummary(string $source): ?string
    {
        return $this->summarizeContributorCollection($this->flattenedContributorsFor($source));
    }

    public function isCalculatedFromComponents(): bool
    {
        return $this->source === 'calculated_components';
    }

    public function hasReducedExpectedBaseline(): bool
    {
        return (bool) ($this->meta['reduced_expected_baseline'] ?? false);
    }

    public function hasHierarchyOverlapWarnings(): bool
    {
        return !empty($this->meta['hierarchy_overlap_warnings']);
    }

    public function hierarchyOverlapSummary(): ?string
    {
        $warnings = collect($this->meta['hierarchy_overlap_warnings'] ?? [])
            ->filter(fn ($warning) => is_array($warning))
            ->values();

        if ($warnings->isEmpty()) {
            return null;
        }

        return $warnings
            ->map(function (array $warning): string {
                $parentLabel = trim((string) ($warning['label'] ?? '')) ?: __('Parent component');
                $childLabels = collect($warning['suppressed_by'] ?? [])
                    ->filter(fn ($child) => is_array($child))
                    ->map(fn (array $child) => trim((string) ($child['label'] ?? '')))
                    ->filter()
                    ->unique(fn (string $label) => mb_strtolower($label))
                    ->values();

                if ($childLabels->isEmpty()) {
                    return __(':parent ignored because an attached child contributes this attribute.', [
                        'parent' => $parentLabel,
                    ]);
                }

                return __(':parent ignored; child value used from :children.', [
                    'parent' => $parentLabel,
                    'children' => $childLabels->implode(', '),
                ]);
            })
            ->implode(' ');
    }

    private function contributorSummaryForClassifications(string $source, array $classifications): ?string
    {
        return $this->summarizeContributorCollection(
            $this->flattenedContributorsFor($source)
                ->filter(fn (array $contributor) => in_array($contributor['classification'] ?? null, $classifications, true))
                ->values()
        );
    }

    private function summarizeContributorCollection(Collection $contributors): ?string
    {
        if ($contributors->isEmpty()) {
            return null;
        }

        $grouped = $contributors
            ->map(function (array $contributor): array {
                $label = trim((string) ($contributor['label'] ?? ''));
                $quantity = max(1, (int) ($contributor['quantity'] ?? 1));

                return [
                    'key' => $label === '' ? '__blank__' : mb_strtolower($label),
                    'label' => $label,
                    'quantity' => $quantity,
                ];
            })
            ->groupBy('key')
            ->map(function (Collection $group): string {
                $label = trim((string) ($group->first()['label'] ?? ''));
                $quantity = $group->sum('quantity');

                if ($label === '') {
                    return $quantity > 1
                        ? __(':count parts', ['count' => $quantity])
                        : __('1 part');
                }

                return $quantity > 1
                    ? __(':label x:count', ['label' => $label, 'count' => $quantity])
                    : $label;
            })
            ->filter()
            ->values();

        if ($grouped->isEmpty()) {
            return null;
        }

        return $grouped->implode(', ');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function flattenedContributorsFor(string $source): Collection
    {
        return collect($this->contributorsFor($source))
            ->flatMap(fn (array $item) => collect($item['contributors'] ?? []))
            ->filter(fn ($contributor) => is_array($contributor))
            ->values();
    }

    private function calculatedSubtotalFor(array $classifications): ?string
    {
        if (!$this->isCalculatedFromComponents() || !$this->definition->isNumericDatatype()) {
            return null;
        }

        $contributors = $this->flattenedContributorsFor('calculated_components')
            ->filter(fn (array $contributor) => in_array($contributor['classification'] ?? null, $classifications, true))
            ->values();

        if ($contributors->isEmpty()) {
            return null;
        }

        $hasValue = false;

        if ($this->definition->datatype === AttributeDefinition::DATATYPE_INT) {
            $sum = 0;

            foreach ($contributors as $contributor) {
                $value = $contributor['value'] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }

                $hasValue = true;
                $sum += ((int) $value) * max(1, (int) ($contributor['quantity'] ?? 1));
            }

            return $hasValue ? (string) $sum : null;
        }

        $sum = 0.0;

        foreach ($contributors as $contributor) {
            $value = $contributor['value'] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            $hasValue = true;
            $sum += ((float) $value) * max(1, (int) ($contributor['quantity'] ?? 1));
        }

        return $hasValue ? $this->trimTrailingZeros($sum) : null;
    }

    private function formatValue(?string $value, ?AttributeOption $option = null): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($this->definition->datatype) {
            AttributeDefinition::DATATYPE_BOOL => $value === '1' ? __('Yes') : __('No'),
            AttributeDefinition::DATATYPE_ENUM => $option && $value === $option->value ? $option->label : $value,
            AttributeDefinition::DATATYPE_INT,
            AttributeDefinition::DATATYPE_DECIMAL => $this->formatNumericValue($value),
            default => $value,
        };
    }

    private function componentValueTakesPrecedence(): bool
    {
        if ($this->hasComparableValue($this->meta['component_resolved_model_value'] ?? null)) {
            return true;
        }

        return in_array($this->source, ['calculated_components', 'installed_components'], true)
            && $this->hasComparableValue($this->meta['current_component_value'] ?? $this->value);
    }

    private function componentValueForManualConflict(): ?string
    {
        return $this->meta['component_resolved_model_value']
            ?? $this->meta['current_component_value']
            ?? $this->meta['expected_component_baseline_value']
            ?? $this->value;
    }

    private function hasComparableValue(?string $value): bool
    {
        return $value !== null && trim($value) !== '';
    }

    private function normalizeComparableValue(?string $value): string
    {
        $value = trim((string) $value);

        if ($this->definition->isNumericDatatype() && is_numeric($value)) {
            return $this->trimTrailingZeros((float) $value);
        }

        if ($this->definition->datatype === AttributeDefinition::DATATYPE_BOOL) {
            return in_array(mb_strtolower($value), ['1', 'true', 'yes'], true) ? '1' : '0';
        }

        return mb_strtolower($value);
    }

    private function optionForValue(?string $value): ?AttributeOption
    {
        if ($value === null || !$this->definition->relationLoaded('options')) {
            return null;
        }

        return $this->definition->options
            ->first(fn (AttributeOption $option) => (string) $option->value === (string) $value);
    }

    private function formatNumericValue(string $value): string
    {
        $unit = trim((string) $this->definition->unit);

        if ($unit === '' || !is_numeric($value)) {
            return $value;
        }

        $displayUnit = $this->displayUnit($unit);
        $separator = in_array($displayUnit, ['"', '%'], true) ? '' : ' ';

        return $value . $separator . $displayUnit;
    }

    private function displayUnit(string $unit): string
    {
        return match (strtolower($unit)) {
            'in',
            'inch',
            'inches' => '"',
            default => $unit,
        };
    }

    private function trimTrailingZeros(float $value): string
    {
        $normalized = rtrim(rtrim(number_format($value, 10, '.', ''), '0'), '.');

        return $normalized === '' ? '0' : $normalized;
    }

    public function toArray(): array
    {
        return [
            'definition' => $this->definition,
            'value' => $this->value,
            'raw_value' => $this->rawValue,
            'option' => $this->option,
            'source' => $this->source,
            'requires_test' => $this->requiresTest,
            'is_override' => $this->isOverride,
            'model_value' => $this->modelValue,
            'model_raw_value' => $this->modelRawValue,
            'manual_model_value' => $this->manualModelValue,
            'manual_model_raw_value' => $this->manualModelRawValue,
            'provenance' => $this->provenance,
            'meta' => $this->meta,
        ];
    }
}
