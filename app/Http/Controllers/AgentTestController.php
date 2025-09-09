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
            'asset_tag' => ['required', 'string'],
            'results' => ['required', 'array'],
            'results.*.test_slug' => ['required', 'string', 'exists:test_types,slug'],
            'results.*.status' => ['required', 'string', 'in:' . implode(',', TestResult::STATUSES)],
            'results.*.note' => ['nullable', 'string'],
        ]);

        $asset = Asset::where('asset_tag', $validated['asset_tag'])->first();
        if (!$asset) {
            return response()->json(['message' => 'Asset not found'], 404);
        }

        $run = new TestRun();
        $run->asset()->associate($asset);
        $run->sku()->associate($asset->sku);
        $run->started_at = now();
        $run->finished_at = now();
        $run->save();

        $types = TestType::forAsset($asset)->get()->keyBy('slug');
        $provided = collect($validated['results'])->keyBy('test_slug');

        foreach ($types as $slug => $type) {
            $data = $provided[$slug] ?? null;

            if ($data) {
                $status = $data['status'];
                $note = $data['note'] ?? null;
            } else {
                $status = TestResult::STATUS_NVT;
                $note = 'Not tested by agent';
            }

            $run->results()->create([
                'test_type_id' => $type->id,
                'status' => $status,
                'note' => $note,
            ]);
        }

        $run->audits()->create([
            'user_id' => null,
            'field' => 'source',
            'before' => null,
            'after' => 'agent',
            'created_at' => now(),
        ]);

        $asset->refreshTestCompletionFlag();

        return response()->json(['test_run_id' => $run->id], 201);
    }
}
