<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\WorkflowProfile;
use Illuminate\Support\Collection;

class WorkflowReadinessService
{
    public function __construct(private readonly WorkflowItemEvidenceService $evidence)
    {
    }

    /**
     * @param Collection<int, WorkflowProfile> $profiles
     * @return array<string, mixed>
     */
    public function summary(Asset $asset, Collection $profiles): array
    {
        $runs = collect();
        $failed = collect();
        $incomplete = collect();
        $missingProfiles = collect();
        $itemStates = collect();
        $profileEvidence = collect();

        foreach ($profiles as $profile) {
            $run = TestRun::query()
                ->where('asset_id', $asset->id)
                ->where('workflow_profile_id', $profile->id)
                ->with(['results.type', 'results.attributeDefinition'])
                ->currentFirst()
                ->first();
            if ($run) {
                $runs->push($run);
            }

            $resolved = $this->evidence->forProfile($asset, $profile, $run);
            $profileEvidence->put((int) $profile->id, $resolved);
            $itemStates = $itemStates->concat($resolved['entries']);

            foreach ($resolved['gating_entries'] as $entry) {
                if ($entry['state'] === WorkflowItemEvidenceService::STATE_FAILED) {
                    $failed->push($entry['label']);
                } elseif (in_array($entry['state'], [
                    WorkflowItemEvidenceService::STATE_PENDING,
                    WorkflowItemEvidenceService::STATE_LEGACY_STALE,
                ], true)) {
                    $incomplete->push($entry['label']);
                }
            }

            if (!$resolved['ready']) {
                $missingProfiles->push($profile->name);
            }
        }

        $legacyAccepted = $itemStates->where('state', WorkflowItemEvidenceService::STATE_LEGACY_ACCEPTED)->values();
        $legacyStale = $itemStates->where('state', WorkflowItemEvidenceService::STATE_LEGACY_STALE)->values();
        $hasActual = $itemStates->contains(fn (array $entry): bool => in_array(
            $entry['state'],
            [WorkflowItemEvidenceService::STATE_PASSED, WorkflowItemEvidenceService::STATE_FAILED],
            true
        ));
        $basis = match (true) {
            $missingProfiles->isNotEmpty() || $failed->isNotEmpty() || $incomplete->isNotEmpty() => 'pending',
            $hasActual && $legacyAccepted->isNotEmpty() => 'mixed',
            $legacyAccepted->isNotEmpty() => 'legacy',
            default => 'actual',
        };

        return [
            'run' => $runs->sortByDesc(fn ($run) => sprintf(
                '%s-%020d',
                optional($run->started_at)->format('Y-m-d H:i:s.u') ?? '',
                $run->id
            ))->first(),
            'runs' => $runs,
            'failed' => $failed->unique()->values(),
            'incomplete' => $incomplete->unique()->values(),
            // Compatibility: this now means unsatisfied requirements, not
            // literally that a workflow_runs row is absent.
            'missing_run' => $missingProfiles->isNotEmpty(),
            'missing_profiles' => $missingProfiles->unique()->values(),
            'missing_requirements' => $incomplete->unique()->values(),
            'item_states' => $itemStates->values(),
            'legacy_accepted' => $legacyAccepted,
            'legacy_stale' => $legacyStale,
            'evidence_basis' => $basis,
            'profile_evidence' => $profileEvidence,
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
