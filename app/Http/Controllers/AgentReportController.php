<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestResult;
use App\Models\TestType;
use App\Models\User;
use App\Models\WorkflowProfile;
use App\Services\WorkflowProgressionService;
use App\Services\WorkflowRunDefinitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AgentReportController extends Controller
{
    private const MAX_RESULTS_PER_REPORT = 100;

    /**
     * Handle a report submission from the local agent.
     */
    public function store(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
        $configuredToken = config('agent.api_token');

        if (
            !is_string($configuredToken)
            || trim($configuredToken) === ''
            || !is_string($token)
            || $token === ''
            || !hash_equals($configuredToken, $token)
        ) {
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
            'asset_tag' => ['required', 'string', 'max:255'],
            'asset_id' => ['nullable', 'integer', 'min:1'],
            'workflow_profile_slug' => ['nullable', 'string', 'max:255'],
            'results' => ['required', 'array', 'min:1', 'max:' . self::MAX_RESULTS_PER_REPORT],
            'results.*.test_slug' => ['required', 'string', 'max:255', 'distinct:strict'],
            'results.*.status' => ['required', 'string', 'in:' . implode(',', TestResult::STATUSES)],
            'results.*.note' => ['nullable', 'string', 'max:10000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        $validated = $validator->validated();

        $assets = Asset::whereRaw('UPPER(TRIM(asset_tag)) = ?', [
            \App\Services\IdentifierDuplicateService::normalize($validated['asset_tag']),
        ])->when(!empty($validated['asset_id']), fn ($query) => $query->whereKey($validated['asset_id']))
            ->limit(2)->get();
        if ($assets->count() > 1) {
            return response()->json(['message' => 'Multiple assets have this tag. Supply asset_id to select the intended asset.'], 409);
        }
        $asset = $assets->first();
        if (!$asset) {
            return response()->json(['message' => 'Asset not found'], 404);
        }

        $agentUserId = config('agent.user_id');
        if ($agentUserId) {
            $configuredAgentUser = User::query()->find($agentUserId);
            if (!$configuredAgentUser || !$configuredAgentUser->isActivated()) {
                return response()->json([
                    'message' => 'The configured agent user is missing or inactive.',
                ], 403);
            }

            Auth::onceUsingId($configuredAgentUser->id);
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

        $agentUser = Auth::user();
        if (
            $profile->execution_level !== WorkflowProfile::EXECUTION_OPERATOR
            && (!$agentUser || !$profile->canBeExecutedBy($agentUser))
        ) {
            return response()->json([
                'message' => 'The configured agent user may not execute this workflow level.',
                'execution_level' => $profile->execution_level,
            ], 403);
        }

        $progressionService = app(WorkflowProgressionService::class);
        $guardDecision = $progressionService->forProfile($asset, $profile);
        if (!$guardDecision || !$guardDecision['can_start']) {
            return $this->workflowGuardResponse($profile, $guardDecision);
        }

        $definition = app(WorkflowRunDefinitionService::class)->forProfile($asset, $profile);
        $resolvedByDefinition = $definition['resolved_by_definition'];
        $types = $definition['profile_items']
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

        $missingBlocking = $profile->blocks_sale_readiness
            ? $types
                ->filter(fn (array $payload) => $payload['profile_item']->is_required)
                ->keys()
                ->diff($provided->keys())
            : collect();

        if ($missingBlocking->isNotEmpty()) {
            return response()->json([
                'message' => 'Missing required workflow results',
                'errors' => [
                    'results' => ['Missing required test slugs: ' . $missingBlocking->implode(', ')],
                ],
            ], 422);
        }

        $outcome = DB::transaction(function () use (
            $agentUserId,
            $asset,
            $profile,
            $provided,
            $types,
            $definition,
            $progressionService
        ): array {
            $lockedAsset = Asset::query()->lockForUpdate()->findOrFail($asset->id);
            $lockedProfile = WorkflowProfile::query()
                ->active()
                ->forAsset($lockedAsset)
                ->whereHas('items')
                ->whereKey($profile->id)
                ->first();
            $lockedDecision = $lockedProfile
                ? $progressionService->forProfile($lockedAsset, $lockedProfile)
                : null;

            if (!$lockedProfile || !$lockedDecision || !$lockedDecision['can_start']) {
                return ['run' => null, 'decision' => $lockedDecision];
            }

            $agentUser = Auth::user();
            if (
                $lockedProfile->execution_level !== WorkflowProfile::EXECUTION_OPERATOR
                && (!$agentUser || !$lockedProfile->canBeExecutedBy($agentUser))
            ) {
                return ['run' => null, 'decision' => array_merge($lockedDecision, [
                    'execution_forbidden' => true,
                ])];
            }

            $lockedDefinition = app(WorkflowRunDefinitionService::class)
                ->forProfile($lockedAsset, $lockedProfile);
            if (!hash_equals(
                $definition['readiness_context_hash'],
                $lockedDefinition['readiness_context_hash']
            )) {
                return ['run' => null, 'decision' => array_merge($lockedDecision, [
                    'definition_changed' => true,
                ])];
            }

            $run = new TestRun();
            $run->asset()->associate($lockedAsset);
            $run->model_number_id = $lockedAsset->getAttribute('model_number_id');
            $run->workflow_profile_id = $lockedProfile->id;
            $run->profile_name_snapshot = $lockedProfile->name;
            $run->profile_slug_snapshot = $lockedProfile->slug;
            $run->profile_display_order_snapshot = $lockedProfile->display_order;
            $run->readiness_context_hash = $lockedDefinition['readiness_context_hash'];
            $run->prerequisite_snapshot = $lockedDecision['prerequisites']->all();
            if ($agentUserId) {
                $run->user_id = $agentUserId;
            }
            $run->started_at = now();
            $run->finished_at = null;
            $run->save();

            foreach ($types as $slug => $payload) {
                /** @var \App\Services\ModelAttributes\ResolvedAttribute $attribute */
                $attribute = $payload['attribute'];
                /** @var TestType $type */
                $type = $payload['type'];
                $profileItem = $payload['profile_item'];
                $data = $provided->get($slug);

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
                    'is_required' => $profileItem->is_required,
                    'result_label_mode' => $profileItem->result_label_mode
                        ?: \App\Models\WorkflowProfileItem::LABEL_MODE_PASS_FAIL,
                    'sort_order' => $profileItem->sort_order,
                ]);
            }

            $run->syncFinishedAtFromResults();

            $run->audits()->create([
                'user_id' => $agentUserId,
                'field' => 'source',
                'before' => null,
                'after' => 'agent',
                'created_at' => now(),
            ]);

            $lockedAsset->refreshTestCompletionFlag();

            return ['run' => $run, 'decision' => null];
        });

        if (!$outcome['run']) {
            return $this->workflowGuardResponse($profile, $outcome['decision']);
        }

        /** @var TestRun $run */
        $run = $outcome['run'];

        Log::info('Agent results received for Asset ' . $asset->getAttribute('asset_tag') . ' by IP ' . $request->ip());

        return response()->json([
            'message' => 'Workflow results recorded',
            'workflow_run_id' => $run->id,
            'test_run_id' => $run->id,
        ]);
    }

    /**
     * The bearer-token agent cannot exercise a human override. It must stop and
     * leave the decision to a permitted, authenticated operator.
     */
    private function workflowGuardResponse(WorkflowProfile $profile, ?array $decision): JsonResponse
    {
        $blockers = collect($decision['blockers'] ?? [])->map(fn (array $blocker): array => [
            'workflow_profile_id' => $blocker['profile_id'],
            'workflow_profile_name' => $blocker['profile_name'],
            'actual_state' => $blocker['actual_state'],
        ])->values();

        return response()->json([
            'message' => 'Workflow cannot be started because its execution guards are not satisfied.',
            'workflow_profile' => [
                'id' => $profile->id,
                'slug' => $profile->slug,
                'name' => $profile->name,
            ],
            'state' => $decision['state'] ?? null,
            'can_continue_in_web_ui' => (bool) ($decision['can_continue'] ?? false),
            'repeat_override_required' => (bool) ($decision['repeat_override_required'] ?? false),
            'repeat_forbidden' => (bool) ($decision['repeat_forbidden'] ?? false),
            'definition_changed' => (bool) ($decision['definition_changed'] ?? false),
            'blockers' => $blockers,
        ], 409);
    }
}
