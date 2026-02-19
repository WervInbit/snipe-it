<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('assets', 'quality_grade')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->string('quality_grade', 32)->nullable()->after('tests_completed_ok');
            });
        }

        $conditionDefinitionId = DB::table('attribute_definitions')
            ->where('key', 'condition_grade')
            ->value('id');

        if (!$conditionDefinitionId) {
            return;
        }

        DB::table('asset_attribute_overrides')
            ->select('id', 'asset_id', 'value', 'raw_value')
            ->where('attribute_definition_id', $conditionDefinitionId)
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $mapped = $this->mapLegacyQuality($row->value ?? $row->raw_value);

                    if (!$mapped) {
                        continue;
                    }

                    DB::table('assets')
                        ->where('id', $row->asset_id)
                        ->whereNull('quality_grade')
                        ->update(['quality_grade' => $mapped]);
                }
            });

        $modelDefaults = DB::table('model_number_attributes')
            ->select('model_number_id', 'value', 'raw_value')
            ->where('attribute_definition_id', $conditionDefinitionId)
            ->get()
            ->mapWithKeys(function ($row) {
                $mapped = $this->mapLegacyQuality($row->value ?? $row->raw_value);

                if (!$mapped) {
                    return [];
                }

                return [(int) $row->model_number_id => $mapped];
            })
            ->all();

        if (empty($modelDefaults)) {
            return;
        }

        DB::table('assets')
            ->select('id', 'model_number_id')
            ->whereNull('quality_grade')
            ->whereNotNull('model_number_id')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($modelDefaults) {
                foreach ($rows as $row) {
                    $mapped = $modelDefaults[(int) $row->model_number_id] ?? null;

                    if (!$mapped) {
                        continue;
                    }

                    DB::table('assets')
                        ->where('id', $row->id)
                        ->whereNull('quality_grade')
                        ->update(['quality_grade' => $mapped]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('assets', 'quality_grade')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->dropColumn('quality_grade');
            });
        }
    }

    private function mapLegacyQuality(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim($value));

        if ($normalized === '') {
            return null;
        }

        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized);
        $normalized = trim((string) $normalized, '_');

        return match ($normalized) {
            'grade_a', 'kwaliteit_a', 'a_kwaliteit', 'quality_a', 'a' => 'grade_a',
            'grade_b', 'kwaliteit_b', 'b_kwaliteit', 'quality_b', 'b' => 'grade_b',
            'grade_c', 'kwaliteit_c', 'c_kwaliteit', 'quality_c', 'c' => 'grade_c',
            'grade_d', 'kwaliteit_d', 'd_kwaliteit', 'quality_d', 'd' => 'grade_d',
            default => null,
        };
    }
};
