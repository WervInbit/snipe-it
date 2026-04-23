<?php

namespace App\Services\Components;

use Illuminate\Support\Collection;

class AssetComponentRoster
{
    /**
     * @param Collection<int, AssetComponentRosterRow> $rows
     * @param array<int, array<string, mixed>> $templateSummaries
     */
    public function __construct(
        public readonly Collection $rows,
        public readonly array $templateSummaries = [],
    ) {
    }

    public function reducedTemplateSummaries(): array
    {
        return array_values(array_filter($this->templateSummaries, fn (array $summary) => ($summary['removed_qty'] ?? 0) > 0));
    }
}
