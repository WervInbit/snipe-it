<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestResult;
use App\Models\TestType;
use App\Models\WorkflowProfile;
use App\Services\ModelAttributes\EffectiveAttributeResolver;
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
        if ($type && !in_array($type, ['test_results', 'workflow_results'], true)) {
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
            'type' => ['required', 'string', 'in:test_results,workflow_results'],
            'asset_tag' => ['required', 'string'],
            'workflow_profile_slug' => ['nullable', 'string'],
            'results' => ['required', 'array'],
            'results.*.test_slug' => ['required', 'string'],
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

        $profile = !empty($validated['workflow_profile_slug'])
            ? WorkflowProfile::query()
                ->active()
                ->forAsset($asset)
                ->whereHas('items')
                ->where('slug', $validated['workflow_profile_slug'])
                ->first()
            : WorkflowProfile::defaultForAsset($asset);

        if (!$profile) {
            return response()->json([
                'message' => 'No active workflow profile found for asset',
            ], 422);
        }

        $run = new TestRun();
        $run->asset()->associate($asset);
        $run->model_number_id = $asset->model_number_id;
        $run->workflow_profile_id = $profile->id;
        $run->profile_name_snapshot = $profile->name;
        $run->profile_slug_snapshot = $profile->slug;
        if ($agentUserId) {
            $run->user_id = $agentUserId;
        }
        $run->started_at = now();
        $run->finished_at = now();
        $run->save();

        $resolver = app(EffectiveAttributeResolver::class);
        $resolvedAttributes = $resolver->resolveForAsset($asset);
        $resolvedByDefinition = $resolvedAttributes->keyBy(fn ($attribute) => $attribute->definition->id);

        $applicableItemIds = TestType::forAsset($asset)->pluck('id')->all();
        $profile->load(['items.item']);
        $types = $profile->items
            ->filter(fn ($profileItem) => $profileItem->item && in_array($profileItem->workflow_item_id, $applicableItemIds, true))
            ->mapWithKeys(function ($profileItem) use ($resolvedByDefinition) {
            $testType = $profileItem->item;
            $attribute = null;

            if ($testType->attribute_definition_id) {
                $attribute = $resolvedByDefinition->get($testType->attribute_definition_id);
            }

            return [
                $testType->slug => [
                    'type' => $testType,
                    'attribute' => $attribute,
                    'profile_item' => $profileItem,
                ],
            ];
        });

        $provided = collect($validated['results'])->keyBy('test_slug');

        $unknown = $provided->keys()->diff($types->keys());
        if ($unknown->isNotEmpty()) {
            return response()->json([
                'message' => 'Unknown test slugs',
                'errors' => [
                    'results' => ['Unexpected test slugs: ' . $unknown->implode(', ')],
                ],
            ], 422);
        }

        foreach ($types as $slug => $payload) {
            /** @var \App\Services\ModelAttributes\ResolvedAttribute $attribute */
            $attribute = $payload['attribute'];
            /** @var TestType $type */
            $type = $payload['type'];
            $profileItem = $payload['profile_item'];
            $data = $provided[$slug] ?? null;

            $status = $data['status'] ?? TestResult::STATUS_NVT;
            $note = $data['note'] ?? ($data ? null : 'Not tested by agent');

            $run->results()->create([
                'workflow_item_id' => $type->id,
                'workflow_profile_item_id' => $profileItem->id,
                'attribute_definition_id' => $attribute?->definition->id,
                'status' => $status,
                'note' => $note,
                'expected_value' => $attribute?->value,
                'expected_raw_value' => $attribute?->rawValue,
                'is_required' => $type->is_required,
                'result_label_mode' => $type->result_label_mode
                    ?: $profileItem->result_label_mode
                    ?: \App\Models\WorkflowProfileItem::LABEL_MODE_PASS_FAIL,
                'sort_order' => $profileItem->sort_order,
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
            'message' => 'Workflow results recorded',
            'workflow_run_id' => $run->id,
            'test_run_id' => $run->id,
        ]);
    }
}

