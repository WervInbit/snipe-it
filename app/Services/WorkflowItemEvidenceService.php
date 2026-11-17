<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\WorkflowLegacyAttestation;
use App\Models\WorkflowProfile;
use App\Models\WorkflowProfileItem;
use Illuminate\Support\Collection;

class WorkflowItemEvidenceService
{
    public const STATE_PASSED = 'passed';
    public const STATE_FAILED = 'failed';
    public const STATE_PENDING = 'pending';
    public const STATE_LEGACY_ACCEPTED = 'legacy_accepted';
    public const STATE_LEGACY_STALE = 'legacy_stale';

    public function __construct(private readonly WorkflowRunDefinitionService $definitions)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function forProfile(Asset $asset, WorkflowProfile $profile, ?TestRun $run = null): array
    {
        $run ??= TestRun::query()
            ->where('asset_id', $asset->id)
            ->where('workflow_profile_id', $profile->id)
            ->with('results')
            ->currentFirst()
            ->first();

        $run?->loadMissing('results');
        $definition = $this->definitions->forProfile(
            $asset,
            $profile,
            $run?->profile_display_order_snapshot
        );
        $knownHashes = [
            $definition['readiness_context_hash'],
            $definition['legacy_readiness_context_hash'],
        ];
        $runIsCurrent = $run !== null
            && (int) $run->model_number_id === (int) $asset->getAttribute('model_number_id')
            && is_string($run->readiness_context_hash)
            && strlen($run->readiness_context_hash) === 64
            && collect($knownHashes)->contains(
                fn (string $hash): bool => hash_equals($hash, $run->readiness_context_hash)
            );

        $resultsByProfileItem = ($run?->results ?? collect())
            ->filter(fn (TestResult $result): bool => $result->workflow_profile_item_id !== null)
            ->groupBy('workflow_profile_item_id');
        $attestations = WorkflowLegacyAttestation::query()
            ->forAsset($asset)
            ->active()
            ->where('workflow_profile_id', $profile->id)
            ->get()
            ->keyBy('workflow_profile_item_id');
        $itemHashes = $definition['profile_item_context_hashes'];

        $entries = $definition['profile_items']->map(function (
            WorkflowProfileItem $profileItem
        ) use (
            $resultsByProfileItem,
            $attestations,
            $itemHashes,
            $runIsCurrent,
            $profile
        ): array {
            $matchingResults = $resultsByProfileItem->get($profileItem->id, collect())
                ->filter(fn (TestResult $result): bool =>
                    (int) $result->workflow_item_id === (int) $profileItem->workflow_item_id
                )
                ->values();
            /** @var TestResult|null $result */
            $result = $matchingResults->count() === 1 ? $matchingResults->first() : null;
            /** @var WorkflowLegacyAttestation|null $attestation */
            $attestation = $attestations->get($profileItem->id);
            $currentItemHash = $itemHashes->get($profileItem->id);
            $state = self::STATE_PENDING;
            $source = 'none';

            if ($result && $runIsCurrent) {
                $state = match ($result->status) {
                    TestResult::STATUS_PASS => self::STATE_PASSED,
                    TestResult::STATUS_FAIL => self::STATE_FAILED,
                    default => self::STATE_PENDING,
                };
                $source = 'result';
            } elseif ($matchingResults->contains(
                fn (TestResult $candidate): bool => $candidate->status === TestResult::STATUS_FAIL
            )) {
                // A real failure remains visible even when a broader run context
                // later becomes stale. Legacy evidence must never hide it.
                $state = self::STATE_FAILED;
                $source = 'stale_result';
            } elseif ($runIsCurrent && $matchingResults->isNotEmpty()) {
                // A malformed current run (for example duplicate results for
                // one profile item) must fail closed. Historical evidence may
                // not conceal inconsistent real execution records.
                $state = self::STATE_PENDING;
                $source = 'invalid_result';
            } elseif ($attestation && is_string($currentItemHash)
                && hash_equals($currentItemHash, $attestation->requirement_context_hash)) {
                $state = self::STATE_LEGACY_ACCEPTED;
                $source = 'legacy';
            } elseif ($attestation) {
                $state = self::STATE_LEGACY_STALE;
                $source = 'legacy_stale';
            }

            return [
                'profile' => $profile,
                'profile_item' => $profileItem,
                'item' => $profileItem->item,
                'label' => $profile->name . ': ' . $profileItem->item->name,
                'required' => (bool) $profileItem->is_required,
                'state' => $state,
                'source' => $source,
                'result' => $result,
                'attestation' => $attestation,
                'requirement_context_hash' => $currentItemHash,
            ];
        })->values();

        $requiredEntries = $entries->where('required', true)->values();
        $gatingEntries = $requiredEntries->isNotEmpty() ? $requiredEntries : $entries;
        $hasActual = $entries->contains(fn (array $entry): bool => $entry['result'] !== null);
        $hasLegacy = $entries->contains(
            fn (array $entry): bool => $entry['state'] === self::STATE_LEGACY_ACCEPTED
        );
        $hasFailure = $gatingEntries->contains(
            fn (array $entry): bool => $entry['state'] === self::STATE_FAILED
        );
        $hasPending = $gatingEntries->contains(fn (array $entry): bool => in_array(
            $entry['state'],
            [self::STATE_PENDING, self::STATE_LEGACY_STALE],
            true
        ));
        $answered = !$hasPending && $gatingEntries->isNotEmpty();
        $ready = $answered
            && !$hasFailure
            && (!$hasActual || ($runIsCurrent && $run?->finished_at !== null));
        $basis = match (true) {
            !$ready => 'pending',
            $hasActual && $hasLegacy => 'mixed',
            $hasLegacy => 'legacy',
            default => 'actual',
        };

        return [
            'profile' => $profile,
            'definition' => $definition,
            'run' => $run,
            'run_is_current' => $runIsCurrent,
            'entries' => $entries,
            'gating_entries' => $gatingEntries,
            'answered' => $answered,
            'ready' => $ready,
            'has_failure' => $hasFailure,
            'has_pending' => $hasPending,
            'has_actual' => $hasActual,
            'has_legacy' => $hasLegacy,
            'basis' => $basis,
            'usable_attestations' => $entries
                ->where('state', self::STATE_LEGACY_ACCEPTED)
                ->pluck('attestation')
                ->filter()
                ->values(),
        ];
    }

    public function syncRunFinishedAt(TestRun $run): void
    {
        $run->loadMissing('asset', 'profile', 'results');
        if (!$run->asset) {
            return;
        }

        if (!$run->profile) {
            $required = $run->results->where('is_required', true);
            $gating = $required->isNotEmpty() ? $required : $run->results;
            $isComplete = $gating->isNotEmpty()
                && $gating->every(fn (TestResult $result): bool => $result->status !== TestResult::STATUS_NVT);

            if ($isComplete && $run->finished_at === null) {
                $run->finished_at = now();
            } elseif (!$isComplete && $run->finished_at !== null) {
                $run->finished_at = null;
            }

            if ($run->isDirty('finished_at')) {
                $run->save();
            }

            return;
        }

        $evidence = $this->forProfile($run->asset, $run->profile, $run);
        $isComplete = $evidence['run_is_current']
            && $evidence['answered'];

        if ($isComplete && $run->finished_at === null) {
            $run->finished_at = now();
        } elseif (!$isComplete && $run->finished_at !== null) {
            $run->finished_at = null;
        }

        if ($run->isDirty('finished_at')) {
            $run->save();
        }
    }
}
