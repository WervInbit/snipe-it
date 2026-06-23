<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('component_instances')) {
            return;
        }

        $rows = DB::table('component_instances')
            ->select('id', 'component_tag')
            ->orderBy('id')
            ->get();

        $reservedTags = $this->reservedTags();

        foreach ($rows as $row) {
            $tag = (string) $row->component_tag;

            if (!preg_match('/^INBIT-([A-Z]{2})(\d{4})$/', $tag, $matches)) {
                continue;
            }

            $newTag = $this->uniqueNamespacedTag($matches[1], (int) $matches[2], (int) $row->id, $reservedTags);

            if ($newTag === $tag) {
                continue;
            }

            DB::table('component_instances')
                ->where('id', $row->id)
                ->update(['component_tag' => $newTag]);

            unset($reservedTags[$tag]);
            $reservedTags[$newTag] = 'component:'.$row->id;
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('component_instances')) {
            return;
        }

        $rows = DB::table('component_instances')
            ->select('id', 'component_tag')
            ->orderBy('id')
            ->get();

        $reservedTags = $this->reservedTags();

        foreach ($rows as $row) {
            $tag = (string) $row->component_tag;

            if (!preg_match('/^INBIT-C-([A-Z]{2})(\d{4})$/', $tag, $matches)) {
                continue;
            }

            $oldTag = 'INBIT-'.$matches[1].$matches[2];

            if (isset($reservedTags[$oldTag]) && $reservedTags[$oldTag] !== 'component:'.$row->id) {
                continue;
            }

            DB::table('component_instances')
                ->where('id', $row->id)
                ->update(['component_tag' => $oldTag]);

            unset($reservedTags[$tag]);
            $reservedTags[$oldTag] = 'component:'.$row->id;
        }
    }

    private function reservedTags(): array
    {
        $reserved = [];

        if (Schema::hasTable('assets')) {
            DB::table('assets')
                ->whereNotNull('asset_tag')
                ->pluck('asset_tag')
                ->each(function ($tag) use (&$reserved): void {
                    $reserved[(string) $tag] = 'asset';
                });
        }

        DB::table('component_instances')
            ->whereNotNull('component_tag')
            ->select('id', 'component_tag')
            ->orderBy('id')
            ->get()
            ->each(function ($row) use (&$reserved): void {
                $reserved[(string) $row->component_tag] = 'component:'.$row->id;
            });

        return $reserved;
    }

    private function uniqueNamespacedTag(string $letters, int $digits, int $componentId, array $reservedTags): string
    {
        for ($offset = 0; $offset <= 9999; $offset++) {
            $candidate = sprintf('INBIT-C-%s%04d', $letters, ($digits + $offset) % 10000);

            if (!isset($reservedTags[$candidate]) || $reservedTags[$candidate] === 'component:'.$componentId) {
                return $candidate;
            }
        }

        return sprintf('INBIT-C-%s%04d', $letters, $digits);
    }
};
