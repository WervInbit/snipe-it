<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\TestType;
use App\Models\WorkflowProfile;
use Illuminate\Support\Collection;

class WorkflowProgressionService
{
    public const STATE_NOT_STARTED = 'not_started';
    public const STATE_IN_PROGRESS = 'in_progress';
    public const STATE_COMPLETED_WITH_ISSUES = 'completed_with_issues';
    public const STATE_COMPLETED_SUCCESSFULLY = 'completed_successfully';
    public const STATE_STALE = 'stale';

    public function __construct(private readonly WorkflowRunDefinitionService $definitions)
    {
    }

    /**
     * @return Collection
     */
    public function forAsset(Asset $asset): Collection
    {
        $profiles = WorkflowProfile::query()
            ->active()
            ->forAsset($asset)
            ->whereHas('items')
            ->with(['prerequisites.items', 'items.item'])
            ->ordered()
            ->get();
        $profiles = $this->sortProfilesByDependencies($profiles);
        $applicableItemIds = TestType::query()
            ->forAsset($asset)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->flip();

        $runsByProfile = TestRun::query()
            ->where('asset_id', $asset->id)
            ->whereIn('workflow_profile_id', $profiles->pluck('id'))
            ->with('results')
            ->currentFirst()
            ->get()
            ->groupBy('workflow_profile_id');

        $rows = $profiles->values()->map(function (
            WorkflowProfile $profile,
            int $index
        ) use (
            $asset,
            $runsByProfile,
            $applicableItemIds
        ): array {
            /** @var TestRun|null $run */
            $run = $runsByProfile->get($profile->id, collect())->first();
            $state = $this->runState($asset, $profile, $run);

            return [
                'position' => $index + 1,
                'profile' => $profile,
                'run' => $run,
                'state' => $state,
                'has_applicable_items' => $profile->items->contains(
                    fn ($profileItem): bool => $profileItem->item !== null
                        && $applicableItemIds->has((int) $profileItem->workflow_item_id)
                ),
            ];
        });

        $resolvedRows = collect();
        $resolvedRowsByProfile = collect();
        $rowProfileIds = $rows
            ->pluck('profile.id')
            ->map(fn ($id): int => (int) $id);

        foreach ($rows as $row) {
            /** @var WorkflowProfile $profile */
            $profile = $row['profile'];
            $row['configuration_blocked'] = !$row['has_applicable_items'];
            $prerequisites = $profile->prerequisites->map(function (
                WorkflowProfile $prerequisite
            ) use ($resolvedRowsByProfile, $rowProfileIds): array {
                $prerequisiteRow = $resolvedRowsByProfile->get($prerequisite->id);

                if (!$prerequisiteRow) {
                    $unresolvedApplicableProfile = $rowProfileIds->contains((int) $prerequisite->id);
                    $configurationBlocked = !$prerequisite->is_active
                        || $prerequisite->items->isEmpty()
                        || $unresolvedApplicableProfile;

                    return [
                        'profile_id' => (int) $prerequisite->id,
                        'profile_name' => $prerequisite->name,
                        'actual_state' => !$prerequisite->is_active
                            ? 'inactive'
                            : ($prerequisite->items->isEmpty()
                                ? 'empty'
                                : ($unresolvedApplicableProfile ? 'invalid_dependency_graph' : 'not_applicable')),
                        'satisfying_run_id' => null,
                        'prerequisites_satisfied' => !$configurationBlocked,
                        'satisfied' => !$configurationBlocked,
                    ];
                }

                $actualState = $prerequisiteRow['configuration_blocked']
                    ? 'empty'
                    : $prerequisiteRow['state'];
                $prerequisiteChainSatisfied = $prerequisiteRow['dependencies_satisfied'];
                $satisfied = !$prerequisiteRow['configuration_blocked']
                    && $actualState === self::STATE_COMPLETED_SUCCESSFULLY
                    && $prerequisiteChainSatisfied;

                return [
                    'profile_id' => (int) $prerequisite->id,
                    'profile_name' => $prerequisite->name,
                    'actual_state' => $actualState,
                    'satisfying_run_id' => $satisfied ? $prerequisiteRow['run']?->id : null,
                    'prerequisites_satisfied' => $prerequisiteChainSatisfied,
                    'satisfied' => $satisfied,
                ];
            })->values();

            $blockers = $prerequisites->where('satisfied', false)->values();
            $hasExistingRun = $row['run'] !== null;

            $row['prerequisites'] = $prerequisites;
            $row['blockers'] = $blockers;
            $row['dependencies_satisfied'] = $blockers->isEmpty();
            $row['can_continue'] = $row['state'] === self::STATE_IN_PROGRESS;
            $row['dependency_override_required'] = $blockers->isNotEmpty() && !$row['can_continue'];
            $row['repeat_override_required'] = $hasExistingRun
                && !$row['can_continue']
                && $profile->repeat_policy !== WorkflowProfile::REPEAT_NEVER;
            $row['repeat_forbidden'] = $hasExistingRun
                && !$row['can_continue']
                && $profile->repeat_policy === WorkflowProfile::REPEAT_NEVER;
            $row['can_start'] = !$row['can_continue']
                && !$row['configuration_blocked']
                && !$row['dependency_override_required']
                && !$row['repeat_override_required']
                && !$row['repeat_forbidden'];
            $row['execution_level'] = $profile->execution_level;

            $resolvedRows->push($row);
            $resolvedRowsByProfile->put((int) $profile->id, $row);
        }

        return $resolvedRows->values();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function forProfile(Asset $asset, WorkflowProfile $profile): ?array
    {
        return $this->forAsset($asset)
            ->first(fn (array $row): bool => (int) $row['profile']->id === (int) $profile->id);
    }

    public function runState(Asset $asset, WorkflowProfile $profile, ?TestRun $run): string
    {
        if (!$run) {
            return self::STATE_NOT_STARTED;
        }

        $definition = $this->definitions->forProfile(
            $asset,
            $profile,
            $run->profile_display_order_snapshot
        );
        $knownHashes = [
            $definition['readiness_context_hash'],
            $definition['legacy_readiness_context_hash'],
        ];
        if (
            (int) $run->model_number_id !== (int) $asset->getAttribute('model_number_id')
            || !is_string($run->readiness_context_hash)
            || strlen($run->readiness_context_hash) !== 64
            || !collect($knownHashes)->contains(
                fn (string $hash): bool => hash_equals($hash, $run->readiness_context_hash)
            )
        ) {
            return self::STATE_STALE;
        }

        $results = $run->relationLoaded('results') ? $run->results : $run->results()->get();
        $requiredProfileItems = $definition['profile_items']
            ->filter(fn ($profileItem): bool => (bool) $profileItem->is_required)
            ->values();
        $completionProfileItems = $requiredProfileItems->isNotEmpty()
            ? $requiredProfileItems
            : $definition['profile_items'];

        if ($completionProfileItems->isEmpty()) {
            return self::STATE_IN_PROGRESS;
        }

        $completionResults = collect();
        foreach ($completionProfileItems as $profileItem) {
            $matchingResults = $results
                ->where('workflow_profile_item_id', $profileItem->id)
                ->values();

            if ($matchingResults->count() !== 1) {
                return self::STATE_IN_PROGRESS;
            }

            /** @var TestResult $result */
            $result = $matchingResults->first();
            if (
                (int) $result->workflow_item_id !== (int) $profileItem->workflow_item_id
                || (bool) $result->is_required !== (bool) $profileItem->is_required
                || $result->status === TestResult::STATUS_NVT
            ) {
                return self::STATE_IN_PROGRESS;
            }

            $completionResults->push($result);
        }

        return $completionResults->contains(
            fn (TestResult $result): bool => $result->status !== TestResult::STATUS_PASS
        )
            ? self::STATE_COMPLETED_WITH_ISSUES
            : self::STATE_COMPLETED_SUCCESSFULLY;
    }

    /**
     * Keep the administrator's display order as the stable preference while
     * guaranteeing that applicable prerequisites are numbered first.
     *
     * @param Collection<int, WorkflowProfile> $profiles
     * @return Collection<int, WorkflowProfile>
     */
    private function sortProfilesByDependencies(Collection $profiles): Collection
    {
        $remaining = $profiles->keyBy(fn (WorkflowProfile $profile): int => (int) $profile->id);
        $applicableIds = $remaining->keys()->map(fn ($id): int => (int) $id)->all();
        $sorted = collect();

        while ($remaining->isNotEmpty()) {
            $next = $remaining->first(function (WorkflowProfile $profile) use ($remaining, $applicableIds): bool {
                return $profile->prerequisites
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->intersect($applicableIds)
                    ->every(fn (int $id): bool => !$remaining->has($id));
            });

            if (!$next) {
                // Persisted cycles are prevented by the admin validator. This
                // fallback keeps the list usable if legacy/manual data is bad.
                return $sorted->concat($remaining->values())->values();
            }

            $sorted->push($next);
            $remaining->forget((int) $next->id);
        }

        return $sorted->values();
    }
}
