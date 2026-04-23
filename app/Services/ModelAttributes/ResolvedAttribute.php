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
        return $this->formatValue($this->value);
    }

    public function formattedModelValue(): ?string
    {
        return $this->formatValue($this->modelValue);
    }

    public function formattedManualModelValue(): ?string
    {
        return $this->formatValue($this->manualModelValue);
    }

    public function formattedCalculatedBaselineValue(): ?string
    {
        return $this->formatValue($this->meta['expected_component_baseline_value'] ?? null);
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

    private function formatValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($this->definition->datatype) {
            AttributeDefinition::DATATYPE_BOOL => $value === '1' ? __('Yes') : __('No'),
            default => $value,
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
