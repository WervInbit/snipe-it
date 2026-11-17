<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\User;
use App\Models\WorkflowLegacyAttestation;
use App\Models\WorkflowProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LegacyWorkflowAttestationService
{
    public function __construct(
        private readonly WorkflowRunDefinitionService $definitions,
        private readonly WorkflowItemEvidenceService $evidence
    ) {
    }

    /**
     * @param array<int, int|string> $profileItemIds
     * @return Collection<int, WorkflowLegacyAttestation>
     */
    public function createForNewAsset(
        Asset $asset,
        array $profileItemIds,
        string $sourceNote,
        ?string $sourceTestedAt,
        User $actor
    ): Collection {
        $requestedIds = collect($profileItemIds)
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->values();

        if ($requestedIds->isEmpty() || $requestedIds->unique()->count() !== $requestedIds->count()) {
            throw ValidationException::withMessages([
                'legacy_profile_item_ids' => trans('tests.legacy_duplicate_selection'),
            ]);
        }

        $lockedAsset = Asset::query()->lockForUpdate()->findOrFail($asset->id);
        $profiles = WorkflowProfile::query()
            ->active()
            ->forAsset($lockedAsset)
            ->whereHas('items')
            ->ordered()
            ->get();
        $applicable = collect();

        foreach ($profiles as $profile) {
            $definition = $this->definitions->forProfile($lockedAsset, $profile);
            foreach ($definition['profile_items'] as $profileItem) {
                $applicable->put((int) $profileItem->id, [
                    'profile' => $profile,
                    'profile_item' => $profileItem,
                    'hash' => $definition['profile_item_context_hashes']->get($profileItem->id),
                ]);
            }
        }

        $unknown = $requestedIds->diff($applicable->keys());
        if ($unknown->isNotEmpty()) {
            throw ValidationException::withMessages([
                'legacy_profile_item_ids' => trans('tests.legacy_selection_changed'),
            ]);
        }

        $evidenceSet = (string) Str::uuid();
        $acceptedAt = now();
        $actorName = $this->actorName($actor);
        $created = collect();

        foreach ($requestedIds as $profileItemId) {
            $context = $applicable->get($profileItemId);
            $profile = $context['profile'];
            $profileItem = $context['profile_item'];
            $item = $profileItem->item;

            $created->push(WorkflowLegacyAttestation::query()->create([
                'evidence_set_uuid' => $evidenceSet,
                'asset_id' => $lockedAsset->id,
                'model_number_id' => $lockedAsset->model_number_id,
                'workflow_profile_id' => $profile->id,
                'workflow_profile_item_id' => $profileItem->id,
                'workflow_item_id' => $item->id,
                'profile_name_snapshot' => $profile->name,
                'profile_slug_snapshot' => $profile->slug,
                'item_name_snapshot' => $item->name,
                'item_slug_snapshot' => $item->slug,
                'requirement_context_hash' => $context['hash'],
                'source_note' => trim($sourceNote),
                'source_tested_at' => $sourceTestedAt,
                'accepted_by' => $actor->id,
                'accepted_by_name_snapshot' => $actorName,
                'accepted_at' => $acceptedAt,
                'active_slot' => 1,
            ]));
        }

        $lockedAsset->refreshTestCompletionFlag();

        return $created;
    }

    public function supersedeForResult(Asset $asset, TestResult $result): void
    {
        if (!$result->workflow_profile_item_id) {
            return;
        }

        WorkflowLegacyAttestation::query()
            ->forAsset($asset)
            ->active()
            ->where('workflow_profile_item_id', $result->workflow_profile_item_id)
            ->lockForUpdate()
            ->get()
            ->each(function (WorkflowLegacyAttestation $attestation) use ($result): void {
                $attestation->forceFill([
                    'superseded_at' => now(),
                    'superseded_by_result_id' => $result->id,
                    'active_slot' => null,
                ])->save();
            });
    }

    public function revoke(
        Asset $asset,
        WorkflowLegacyAttestation $attestation,
        User $actor,
        string $reason
    ): void {
        DB::transaction(function () use ($asset, $attestation, $actor, $reason): void {
            $lockedAsset = Asset::query()->lockForUpdate()->findOrFail($asset->id);
            $locked = WorkflowLegacyAttestation::query()
                ->lockForUpdate()
                ->where('asset_id', $lockedAsset->id)
                ->findOrFail($attestation->id);

            if ($locked->revoked_at || $locked->superseded_at) {
                throw ValidationException::withMessages([
                    'revocation_reason' => trans('tests.legacy_no_longer_active'),
                ]);
            }

            $locked->forceFill([
                'revoked_by' => $actor->id,
                'revoked_by_name_snapshot' => $this->actorName($actor),
                'revoked_at' => now(),
                'revocation_reason' => trim($reason),
                'active_slot' => null,
            ])->save();

            $this->materializePendingResultForCurrentRun($lockedAsset, $locked);
            $lockedAsset->refreshTestCompletionFlag();
        });
    }

    private function materializePendingResultForCurrentRun(
        Asset $asset,
        WorkflowLegacyAttestation $attestation
    ): void {
        $run = TestRun::query()
            ->where('asset_id', $asset->id)
            ->where('workflow_profile_id', $attestation->workflow_profile_id)
            ->currentFirst()
            ->lockForUpdate()
            ->first();

        if (!$run || $run->results()->where('workflow_profile_item_id', $attestation->workflow_profile_item_id)->exists()) {
            return;
        }

        $snapshotIds = collect($run->legacy_attestation_snapshot ?? [])->pluck('id')->map(fn ($id) => (int) $id);
        if (!$snapshotIds->contains((int) $attestation->id) || !$run->profile) {
            return;
        }

        $definition = $this->definitions->forProfile($asset, $run->profile, $run->profile_display_order_snapshot);
        $profileItem = $definition['profile_items']->firstWhere('id', $attestation->workflow_profile_item_id);
        if (!$profileItem || !$profileItem->item) {
            return;
        }

        $item = $profileItem->item;
        $attribute = $item->attribute_definition_id
            ? $definition['resolved_by_definition']->get($item->attribute_definition_id)
            : null;
        $run->results()->create([
            'workflow_item_id' => $item->id,
            'workflow_profile_item_id' => $profileItem->id,
            'attribute_definition_id' => $item->attribute_definition_id,
            'status' => TestResult::STATUS_NVT,
            'note' => null,
            'expected_value' => $attribute?->value,
            'expected_raw_value' => $attribute?->rawValue,
            'is_required' => $profileItem->is_required,
            'result_label_mode' => $profileItem->result_label_mode,
            'sort_order' => $profileItem->sort_order,
        ]);
        $this->evidence->syncRunFinishedAt($run->fresh());
    }

    private function actorName(User $actor): string
    {
        $name = trim((string) $actor->first_name . ' ' . (string) $actor->last_name);

        return $name !== '' ? $name : (string) $actor->username;
    }
}
