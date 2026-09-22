<?php

namespace App\Services\Assets;

use App\Models\Asset;
use App\Models\Statuslabel;
use App\Services\Components\AttachedComponentIssueService;

class AssetStatusTransitionGuardService
{
    public function __construct(private readonly AttachedComponentIssueService $componentIssues)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function evaluate(Asset $asset, Statuslabel $targetStatus): array
    {
        $readinessTransition = Asset::statusRequiresTestAck($targetStatus);
        $lockedExit = $this->isLockedExit($asset, $targetStatus);
        $workflowIssues = $readinessTransition ? $this->workflowIssueLines($asset) : [];
        $componentIssues = $readinessTransition
            ? $this->componentIssues->warningLinesForAsset($asset)
            : [];
        $protected = $readinessTransition || $lockedExit;
        $fromStatusId = (int) $asset->getAttribute('status_id');
        $updatedAt = $asset->getAttribute('updated_at');
        $fingerprintPayload = [
            'asset_id' => (int) $asset->id,
            'from_status_id' => $fromStatusId,
            'to_status_id' => (int) $targetStatus->id,
            'asset_updated_at' => $updatedAt instanceof \DateTimeInterface
                ? $updatedAt->format('Y-m-d H:i:s.u')
                : (string) $updatedAt,
            'workflow_issues' => array_values($workflowIssues),
            'component_issues' => array_values($componentIssues),
            'locked_exit' => $lockedExit,
            'requires_reason' => $lockedExit || $workflowIssues !== [] || $componentIssues !== [],
            'required_identifier' => $lockedExit ? (string) $asset->asset_tag : null,
        ];

        return [
            'protected' => $protected,
            'from_status' => [
                'id' => $fromStatusId,
                'name' => $asset->assetstatus?->name ?? __('Unknown'),
            ],
            'to_status' => [
                'id' => (int) $targetStatus->id,
                'name' => (string) $targetStatus->getAttribute('name'),
            ],
            'workflow_issues' => array_values($workflowIssues),
            'component_issues' => array_values($componentIssues),
            'has_issues' => $workflowIssues !== [] || $componentIssues !== [],
            'locked_exit' => $lockedExit,
            'requires_reason' => $fingerprintPayload['requires_reason'],
            'required_identifier' => $fingerprintPayload['required_identifier'],
            'confirmation_hash' => $this->hash($fingerprintPayload),
        ];
    }

    public function isLockedExit(Asset $asset, Statuslabel $targetStatus): bool
    {
        $asset->loadMissing('assetstatus');

        return (int) $asset->status_id !== (int) $targetStatus->id
            && $this->isLockedStatus($asset->assetstatus);
    }

    public function isLockedStatus(?Statuslabel $status): bool
    {
        return $status?->hasLifecycleStage(
            Statuslabel::LIFECYCLE_SOLD,
            Statuslabel::LIFECYCLE_BROKEN_PARTS,
            Statuslabel::LIFECYCLE_DESTROYED
        ) ?? false;
    }

    public function identifierMatches(Asset $asset, ?string $identifier): bool
    {
        return mb_strtoupper(trim((string) $identifier)) === mb_strtoupper(trim((string) $asset->asset_tag));
    }

    /**
     * @return array<int, string>
     */
    private function workflowIssueLines(Asset $asset): array
    {
        if (
            $asset->blockingSaleReadinessProfiles()->isEmpty()
            && (bool) $asset->getAttribute('tests_completed_ok')
        ) {
            return [];
        }

        $summary = $asset->latestTestIssueSummary();
        $lines = collect();
        $missingProfiles = $summary['missing_profiles'];

        if ($missingProfiles->isNotEmpty()) {
            $lines->push(trans('tests.missing_workflow_profiles', [
                'profiles' => $missingProfiles->implode(', '),
            ]));
        } elseif ($summary['missing_run']) {
            $lines->push(trans('tests.no_test_run_recorded'));
        }

        if ($summary['failed']->isNotEmpty()) {
            $lines->push(trans('tests.failed_list', ['tests' => $summary['failed']->implode(', ')]));
        }

        if ($summary['incomplete']->isNotEmpty()) {
            $lines->push(trans('tests.incomplete_list', ['tests' => $summary['incomplete']->implode(', ')]));
        }

        return $lines->values()->all();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hash(array $payload): string
    {
        return hash_hmac(
            'sha256',
            json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            (string) config('app.key')
        );
    }
}
