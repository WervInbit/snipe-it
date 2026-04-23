<?php

namespace App\Services\Components;

use App\Models\ComponentInstance;
use App\Models\ModelNumberComponentTemplate;

class AssetComponentRosterRow
{
    public function __construct(
        public readonly string $classification,
        public readonly string $label,
        public readonly string $name,
        public readonly ?ModelNumberComponentTemplate $template = null,
        public readonly ?ComponentInstance $component = null,
        public readonly ?string $installedAs = null,
        public readonly bool $tracked = false,
    ) {
    }

    public function isExpected(): bool
    {
        return in_array($this->classification, ['expected', 'expected_tracked'], true);
    }

    public function isTrackedExpected(): bool
    {
        return $this->classification === 'expected_tracked';
    }

    public function isExtra(): bool
    {
        return $this->classification === 'extra';
    }

    public function isCustom(): bool
    {
        return $this->classification === 'custom';
    }

    public function isRemoved(): bool
    {
        return $this->classification === 'removed';
    }
}
