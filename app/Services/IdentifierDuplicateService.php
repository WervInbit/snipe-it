<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\ComponentInstance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class IdentifierDuplicateService
{
    public static function normalize(?string $value): string
    {
        return mb_strtoupper(trim((string) $value), 'UTF-8');
    }

    public function matches(string $field, string $value, ?Model $except = null, bool $lock = false): Collection
    {
        $value = self::normalize($value);
        if ($value === '') {
            return collect();
        }
        $matches = collect();
        foreach (['asset' => ['assets', 'asset_tag'], 'component' => ['component_instances', 'component_tag']] as $type => [$table, $tagColumn]) {
            $column = $field === 'tag' ? $tagColumn : 'serial';
            $query = DB::table($table)->whereRaw('UPPER(TRIM(' . $column . ')) = ?', [$value]);
            if ($except && $except->getTable() === $table && $except->exists) {
                $query->where('id', '<>', $except->getKey());
            }
            if ($lock) {
                $query->lockForUpdate();
            }
            foreach ($query->get(['id', 'deleted_at']) as $row) {
                $matches->push(['type' => $type, 'id' => (int) $row->id, 'deleted' => $row->deleted_at !== null]);
            }
        }
        return $matches;
    }

    public function summary(string $field, string $value, ?Model $except = null): array
    {
        $matches = $this->matches($field, $value, $except);
        $visible = [];
        foreach ($matches->take(50) as $match) {
            $class = $match['type'] === 'asset' ? Asset::class : ComponentInstance::class;
            $record = $class::query()->find($match['id']);
            if (!$record || !Gate::allows('view', $record)) {
                continue;
            }
            $visible[] = [
                'type' => $match['type'],
                'id' => $record->id,
                'label' => $match['type'] === 'asset' ? $record->asset_tag : $record->component_tag,
                'name' => $match['type'] === 'asset' ? $record->name : $record->display_name,
                'url' => route($match['type'] === 'asset' ? 'hardware.show' : 'components.show', $record),
            ];
        }
        return ['duplicate' => $matches->isNotEmpty(), 'count' => $matches->count(), 'matches' => $visible];
    }
}
