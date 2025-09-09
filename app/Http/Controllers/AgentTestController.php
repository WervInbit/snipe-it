<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestResult;
use App\Models\TestType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentTestController extends Controller
{
    public function store(Request $request): JsonResponse
    {

        $token = $request->bearerToken();
        if (!$token || !hash_equals(config('agent.api_token'), $token)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'asset_tag' => ['required_without:asset_id', 'string'],
            'asset_id' => ['required_without:asset_tag', 'integer', 'exists:assets,id'],
            'results' => ['required', 'array'],
            'results.*.test_slug' => ['required', 'string', 'exists:test_types,slug'],
            'results.*.status' => ['required', 'string', 'in:' . implode(',', TestResult::STATUSES)],
            'results.*.note' => ['nullable', 'string'],
        ]);

        $asset = isset($validated['asset_id'])
            ? Asset::findOrFail($validated['asset_id'])
            : Asset::where('asset_tag', $validated['asset_tag'])->firstOrFail();

        $run = new TestRun();
        $run->asset()->associate($asset);
        $run->sku()->associate($asset->sku);
        $run->started_at = now();
        $run->finished_at = now();
        $run->save();

        foreach ($validated['results'] as $result) {
            $type = TestType::where('slug', $result['test_slug'])->first();
            $run->results()->create([
                'test_type_id' => $type->id,
                'status' => $result['status'],
                'note' => $result['note'] ?? null,
            ]);
        }

        $asset->refreshTestCompletionFlag();

        return response()->json(['test_run_id' => $run->id], 201);
    }
}
