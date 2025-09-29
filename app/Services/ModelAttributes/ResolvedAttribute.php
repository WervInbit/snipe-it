<?php

namespace App\Services\ModelAttributes;

use App\Models\AttributeDefinition;
use App\Models\AttributeOption;

class ResolvedAttribute
{
    public function __construct(
        public readonly AttributeDefinition $definition,
        public readonly ?string $value,
        public readonly ?string $rawValue,
        public readonly ?AttributeOption $option,
        public readonly string $source,
        public readonly bool $requiresTest,
        public readonly bool $isOverride,
        public readonly ?string $modelValue,
        public readonly ?string $modelRawValue
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
        ];
    }
}
