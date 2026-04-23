<?php

namespace App\Services\ModelAttributes;

use App\Models\AttributeDefinition;
use App\Models\AttributeOption;

class ComponentAttributeAggregate
{
    /**
     * @param array<int, array<string, mixed>> $contributors
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public readonly AttributeDefinition $definition,
        public readonly string $value,
        public readonly ?string $rawValue,
        public readonly ?AttributeOption $option,
        public readonly string $source,
        public readonly array $contributors,
        public readonly array $meta = []
    ) {
    }
}
