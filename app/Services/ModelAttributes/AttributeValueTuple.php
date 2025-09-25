<?php

namespace App\Services\ModelAttributes;

class AttributeValueTuple
{
    public function __construct(
        public readonly string $value,
        public readonly ?string $rawValue,
        public readonly ?int $attributeOptionId
    ) {
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'raw_value' => $this->rawValue,
            'attribute_option_id' => $this->attributeOptionId,
        ];
    }
}
