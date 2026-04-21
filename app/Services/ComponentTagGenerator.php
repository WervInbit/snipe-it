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
        return sprintf('INBIT-%s%04d', $this->randomLetters(2), random_int(0, 9999));
    }

    protected function randomLetters(int $length): string
    {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $letters[random_int(0, strlen($letters) - 1)];
        }

        return $result;
    }

    protected function tagExists(string $tag): bool
    {
        return ComponentInstance::withTrashed()->where('component_tag', $tag)->exists()
            || Asset::withTrashed()->where('asset_tag', $tag)->exists();
    }
}
