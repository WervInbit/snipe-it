<?php

namespace App\Services;

use App\Models\ComponentEvent;
use App\Models\ComponentInstance;
use App\Models\User;

class ComponentEventWriter
{
    public function write(ComponentInstance $instance, string $eventType, array $attributes = []): ComponentEvent
    {
        $performedBy = $attributes['performed_by'] ?? auth()->id();

        return $instance->events()->create([
            'event_type' => $eventType,
            'performed_by' => $performedBy instanceof User ? $performedBy->id : $performedBy,
            'from_status' => $attributes['from_status'] ?? null,
            'to_status' => $attributes['to_status'] ?? null,
            'from_asset_id' => $attributes['from_asset_id'] ?? null,
            'to_asset_id' => $attributes['to_asset_id'] ?? null,
            'from_storage_location_id' => $attributes['from_storage_location_id'] ?? null,
            'to_storage_location_id' => $attributes['to_storage_location_id'] ?? null,
            'held_by_user_id' => $attributes['held_by_user_id'] ?? null,
            'related_work_order_id' => $attributes['related_work_order_id'] ?? null,
            'related_work_order_task_id' => $attributes['related_work_order_task_id'] ?? null,
            'note' => $attributes['note'] ?? null,
            'payload_json' => $attributes['payload_json'] ?? null,
            'created_at' => $attributes['created_at'] ?? now(),
        ]);
    }
}
