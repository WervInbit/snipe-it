<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\ComponentInstance;

class ComponentTagGenerator
{
    public function generate(): string
    {
        do {
            $tag = $this->nextCandidate();
        } while ($this->tagExists($tag));

        return $tag;
    }

    protected function nextCandidate(): string
    {
        return app(SequentialTagGenerator::class)->generate('INBIT-C-');
    }

    protected function tagExists(string $tag): bool
    {
        return ComponentInstance::withoutGlobalScopes()->where('component_tag', $tag)->exists()
            || Asset::withoutGlobalScopes()->where('asset_tag', $tag)->exists();
    }
}
