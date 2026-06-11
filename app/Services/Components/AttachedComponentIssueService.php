<?php

namespace App\Services\Components;

use App\Models\Asset;
use App\Models\ComponentInstance;
use Illuminate\Support\Collection;

class AttachedComponentIssueService
{
    /**
     * @return Collection<int, ComponentInstance>
     */
    public function issueComponentsForAsset(Asset $asset): Collection
    {
        return ComponentInstance::query()
            ->with('componentDefinition')
            ->where('current_asset_id', $asset->id)
            ->where(function ($query): void {
                $query->where('lifecycle_status', ComponentInstance::LIFECYCLE_ATTACHED)
                    ->orWhere('status', ComponentInstance::STATUS_INSTALLED);
            })
            ->where(function ($query): void {
                $query->whereIn('condition_status', ComponentInstance::attachmentWarningConditionStatuses())
                    ->orWhere(function ($legacyQuery): void {
                        $legacyQuery
                            ->whereNull('condition_status')
                            ->where(function ($legacyStateQuery): void {
                                $legacyStateQuery
                                    ->whereIn('status', [
                                        ComponentInstance::STATUS_NEEDS_VERIFICATION,
                                        ComponentInstance::STATUS_DEFECTIVE,
                                    ])
                                    ->orWhereIn('condition_code', [
                                        ComponentInstance::CONDITION_UNKNOWN,
                                        ComponentInstance::CONDITION_POOR,
                                        ComponentInstance::CONDITION_BROKEN,
                                    ]);
                            });
                    });
            })
            ->orderBy('parent_component_instance_id')
            ->orderBy('display_name')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    public function warningLinesForAsset(Asset $asset): array
    {
        return $this->issueComponentsForAsset($asset)
            ->map(function (ComponentInstance $component): string {
                $condition = $component->conditionBadgeLabel()
                    ?? $component->displayConditionLabel();

                return $component->display_name . ' - ' . $condition;
            })
            ->values()
            ->all();
    }
}
