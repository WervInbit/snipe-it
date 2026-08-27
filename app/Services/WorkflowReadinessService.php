<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\WorkflowProfile;
use Illuminate\Support\Collection;

class WorkflowReadinessService
{
    public function __construct(private readonly WorkflowRunDefinitionService $definitions)
    {
    }

    /**
     * @param Collection<int, WorkflowProfile> $profiles
     * @return array{run: mixed, runs: Collection, failed: Collection, incomplete: Collection, missing_run: bool, missing_profiles: Collection}
     */
    public function summary(Asset $asset, Collection $profiles): array
    {
        $runs = collect();
        $failed = collect();
        $incomplete = collect();
        $missingProfiles = collect();

        foreach ($profiles as $profile) {
            $definition = $this->definitions->forProfile($asset, $profile);
            $requiredItems = $definition['profile_items']
                ->filter(fn ($profileItem): bool => (bool) $profileItem->item->is_required)
                ->values();

            $run = TestRun::query()
                ->where('asset_id', $asset->id)
                ->where('workflow_profile_id', $profile->id)
                ->with(['results.type', 'results.attributeDefinition'])
                ->orderByRaw('COALESCE(finished_at, created_at) DESC')
                ->orderByDesc('id')
                ->first();

            if (
                !$run
                || !$run->finished_at
                || $requiredItems->isEmpty()
                || (int) $run->model_number_id !== (int) $asset->model_number_id
                || !is_string($run->readiness_context_hash)
                || strlen($run->readiness_context_hash) !== 64
                || !hash_equals($definition['readiness_context_hash'], $run->readiness_context_hash)
            ) {
                $missingProfiles->push($profile->name);
                if ($run) {
                    $runs->push($run);
                }
                continue;
            }

            $runs->push($run);
            $resultsByItem = $run->results->groupBy('workflow_item_id');

            foreach ($requiredItems as $profileItem) {
                $item = $profileItem->item;
                $matchingResults = $resultsByItem->get($item->id, collect());
                $label = $profile->name . ': ' . $item->name;

                if (
                    $matchingResults->count() !== 1
                    || (int) $matchingResults->first()->workflow_profile_item_id !== (int) $profileItem->id
                ) {
                    $incomplete->push($label);
                    continue;
                }

                $result = $matchingResults->first();
                if ($result->status === TestResult::STATUS_FAIL) {
                    $failed->push($label);
                } elseif ($result->status !== TestResult::STATUS_PASS) {
                    $incomplete->push($label);
                }
            }
        }

        return [
            'run' => $runs->sortByDesc(fn ($run) => $run->finished_at ?? $run->created_at)->first(),
            'runs' => $runs,
            'failed' => $failed->values(),
            'incomplete' => $incomplete->values(),
            'missing_run' => $missingProfiles->isNotEmpty(),
            'missing_profiles' => $missingProfiles->values(),
        ];
    }

    public function isReady(Asset $asset, Collection $profiles): bool
    {
        $summary = $this->summary($asset, $profiles);

        return !$summary['missing_run']
            && $summary['failed']->isEmpty()
            && $summary['incomplete']->isEmpty();
    }
}
