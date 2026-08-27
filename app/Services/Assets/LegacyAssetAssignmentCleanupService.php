<?php

namespace App\Services\Assets;

use App\Models\Asset;
use App\Models\CheckoutAcceptance;
use App\Models\Statuslabel;
use Illuminate\Support\Facades\DB;

class LegacyAssetAssignmentCleanupService
{
    public function statusRetiresAssignment(?Statuslabel $status): bool
    {
        return $status
            && ! in_array($status->getStatuslabelType(), ['pending', 'deployable'], true);
    }

    /**
     * @return array<int, string>
     */
    public function assetMorphTypes(): array
    {
        return array_values(array_unique([
            (new Asset())->getMorphClass(),
            Asset::class,
        ]));
    }

    public function isAssetAcceptance(CheckoutAcceptance $acceptance): bool
    {
        return in_array($acceptance->checkoutable_type, $this->assetMorphTypes(), true);
    }

    /**
     * Clear assignment-only state without creating a new checkout/checkin event.
     *
     * Historical checkout records and completed acceptances remain intact. Only
     * pending acceptances, which can no longer be completed for an unavailable
     * asset, are retired.
     */
    public function clear(Asset $asset): int
    {
        return DB::transaction(function () use ($asset): int {
            $pendingAcceptancesDeleted = CheckoutAcceptance::query()
                ->whereIn('checkoutable_type', $this->assetMorphTypes())
                ->where('checkoutable_id', $asset->getKey())
                ->pending()
                ->delete();

            $clearedAttributes = [
                'assigned_to' => null,
                'assigned_type' => null,
                'accepted' => null,
                'expected_checkin' => null,
            ];

            DB::table($asset->getTable())
                ->where($asset->getKeyName(), $asset->getKey())
                ->update($clearedAttributes);

            $asset->forceFill($clearedAttributes);
            $asset->syncOriginalAttributes(array_keys($clearedAttributes));
            $asset->unsetRelation('assignedTo');

            return $pendingAcceptancesDeleted;
        });
    }

    /**
     * Retire an imported pending asset acceptance without accepting or declining it.
     */
    public function retirePendingAcceptance(CheckoutAcceptance $acceptance): bool
    {
        if (!$acceptance->isPending() || !$this->isAssetAcceptance($acceptance)) {
            return false;
        }

        $asset = Asset::withTrashed()->find($acceptance->checkoutable_id);
        if ($asset) {
            $this->clear($asset);
        } else {
            $acceptance->delete();
        }

        return true;
    }
}
