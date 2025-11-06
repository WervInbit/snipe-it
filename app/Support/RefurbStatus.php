<?php

namespace App\Support;

use Illuminate\Support\Facades\Lang;

class RefurbStatus
{
    /**
     * Map canonical status label names to translation keys.
     */
    private const TRANSLATION_KEYS = [
        'Stand-by' => 'stand_by',
        'Being Processed' => 'being_processed',
        'QA Hold' => 'qa_hold',
        'Ready for Sale' => 'ready_for_sale',
        'Sold' => 'sold',
        'Broken / Parts' => 'broken_parts',
        'Internal Use' => 'internal_use',
        'Archived' => 'archived',
        'Returned / RMA' => 'returned_rma',
    ];

    /**
     * Resolve the localized display name for a status.
     */
    public static function displayName(string $status): string
    {
        $key = self::TRANSLATION_KEYS[$status] ?? null;

        if ($key && Lang::has('refurb.statuses.' . $key)) {
            return trans('refurb.statuses.' . $key);
        }

        return $status;
    }
}
