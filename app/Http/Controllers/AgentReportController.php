<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestResult;
use App\Models\TestType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AgentReportController extends Controller
{
    /**
     * Handle a report submission from the local agent.
     */
    public function store(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
        if (!$token || !hash_equals(config('agent.api_token'), $token)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $allowedIps = config('agent.allowed_ips');
        if (!empty($allowedIps) && !in_array($request->ip(), $allowedIps, true)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $type = $request->input('type');
        if ($type && $type !== 'test_results') {
            return response()->json(['message' => 'Unsupported report type'], 400);
        }

        // Future report types (e.g. wipe certificates) can be dispatched here.
        return $this->handleTestResults($request);
    }

    /**
     * Store a test results report and its associated outcomes.
     */
    protected function handleTestResults(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'type' => ['required', 'string', 'in:test_results'],
            'asset_tag' => ['required', 'string'],
            'results' => ['required', 'array'],
            'results.*.test_slug' => ['required', 'string', 'exists:test_types,slug'],
            'results.*.status' => ['required', 'string', 'in:' . implode(',', TestResult::STATUSES)],
            'results.*.note' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        $validated = $validator->validated();

        $asset = Asset::where('asset_tag', $validated['asset_tag'])->first();
        if (!$asset) {
            return response()->json(['message' => 'Asset not found'], 404);
        }

        $agentUserId = config('agent.user_id');
        if ($agentUserId) {
            Auth::onceUsingId($agentUserId);
        }

        $run = new TestRun();
        $run->asset()->associate($asset);
        $run->sku()->associate($asset->sku);
        if ($agentUserId) {
            $run->user_id = $agentUserId;
        }
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
            'user_id' => $agentUserId,
            'field' => 'source',
            'before' => null,
            'after' => 'agent',
            'created_at' => now(),
        ]);

        $asset->refreshTestCompletionFlag();

        Log::info('Agent results received for Asset ' . $asset->asset_tag . ' by IP ' . $request->ip());

        return response()->json([
            'message' => 'Test results recorded',
            'test_run_id' => $run->id,
        ]);
    }
}
