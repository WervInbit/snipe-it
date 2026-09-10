<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\ComponentInstance;
use App\Services\IdentifierDuplicateService;
use App\Support\SameOriginRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IdentifierController extends Controller
{
    public function check(Request $request, IdentifierDuplicateService $duplicates)
    {
        $data = $request->validate([
            'type' => ['required', 'in:asset,component'],
            'field' => ['required', 'in:tag,serial'],
            'value' => ['nullable', 'string', 'max:255'],
            'record_id' => ['nullable', 'integer'],
        ]);
        $class = $data['type'] === 'asset' ? Asset::class : ComponentInstance::class;
        $record = !empty($data['record_id']) ? $class::find($data['record_id']) : null;
        abort_if(!empty($data['record_id']) && !$record, 404);
        if ($record) {
            $this->authorize('view', $record);
        } else {
            $this->authorize('create', $class);
        }
        $column = $data['field'] === 'serial' ? 'serial' : ($data['type'] === 'asset' ? 'asset_tag' : 'component_tag');
        return response()->json($duplicates->summary($data['field'], $data['value'] ?? '', $record) + [
            'unchanged' => $record && IdentifierDuplicateService::normalize($data['value'] ?? '')
                === IdentifierDuplicateService::normalize($record->{$column}),
        ]);
    }

    public function choose(Request $request, IdentifierDuplicateService $duplicates)
    {
        $data = $request->validate([
            'field' => ['required', 'in:tag,serial'],
            'value' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'in:asset,component'],
            'selected_type' => ['nullable', 'in:asset,component'],
            'selected_id' => ['nullable', 'integer'],
            'return_to' => ['nullable', 'string', 'max:2048'],
        ]);
        $records = collect();
        foreach ($duplicates->matches($data['field'], $data['value']) as $match) {
            if (!empty($data['type']) && $data['type'] !== $match['type']) {
                continue;
            }
            $class = $match['type'] === 'asset' ? Asset::class : ComponentInstance::class;
            $record = $class::query()->find($match['id']);
            if ($record && Gate::allows('view', $record)) {
                $records->push(['type' => $match['type'], 'record' => $record]);
            }
        }
        if (!empty($data['selected_id'])) {
            $selected = $records->first(fn ($entry) => $entry['type'] === ($data['selected_type'] ?? '')
                && $entry['record']->id === (int) $data['selected_id']);
            abort_unless($selected, 404);
            return $this->destination($selected, $data['return_to'] ?? null);
        }
        if ($records->count() === 1) {
            return $this->destination($records->first(), $data['return_to'] ?? null);
        }
        return view('identifiers.choose', compact('records', 'data'));
    }

    private function destination(array $entry, ?string $returnTo)
    {
        $returnTo = SameOriginRedirect::sanitize($returnTo);
        if ($entry['type'] === 'asset' && $returnTo) {
            $parts = explode('#', $returnTo, 2);
            $pathAndQuery = explode('?', $parts[0], 2);
            parse_str($pathAndQuery[1] ?? '', $query);
            $query['destination_asset_id'] = $entry['record']->id;
            $url = $pathAndQuery[0] . '?' . http_build_query($query);
            return redirect()->to($url . (isset($parts[1]) ? '#' . $parts[1] : ''));
        }
        return redirect()->route($entry['type'] === 'asset' ? 'hardware.show' : 'components.show', $entry['record']);
    }
}
