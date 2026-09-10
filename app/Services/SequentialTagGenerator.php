<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class SequentialTagGenerator
{
    private const NUMBERS_PER_BLOCK = 9999;

    private const CAPACITY = 26 * 26 * self::NUMBERS_PER_BLOCK;

    public function generate(string $prefix): string
    {
        return DB::transaction(function () use ($prefix) {
            do {
                // The write locks the counter before reading it, including on SQLite.
                $reserved = DB::table('identifier_sequences')
                    ->where('prefix', $prefix)
                    ->where('next_value', '<=', self::CAPACITY)
                    ->increment('next_value');

                if ($reserved !== 1) {
                    throw new RuntimeException('Tag sequence is missing or exhausted: ' . $prefix);
                }

                $ordinal = (int) DB::table('identifier_sequences')
                    ->where('prefix', $prefix)->value('next_value') - 2;
                $block = intdiv($ordinal, self::NUMBERS_PER_BLOCK);
                $letters = chr(65 + intdiv($block, 26)) . chr(65 + $block % 26);
                $tag = sprintf('%s%s%04d', $prefix, $letters, $ordinal % self::NUMBERS_PER_BLOCK + 1);
            } while (
                DB::table('assets')->whereRaw('UPPER(TRIM(asset_tag)) = ?', [$tag])->exists()
                || DB::table('component_instances')->whereRaw('UPPER(TRIM(component_tag)) = ?', [$tag])->exists()
            );

            return $tag;
        }, 5);
    }
}
