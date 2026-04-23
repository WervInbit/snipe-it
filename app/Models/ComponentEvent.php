<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ComponentEvent extends SnipeModel
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'component_events';

    protected $fillable = [
        'component_instance_id',
        'event_type',
        'performed_by',
        'from_status',
        'to_status',
        'from_asset_id',
        'to_asset_id',
        'from_storage_location_id',
        'to_storage_location_id',
        'held_by_user_id',
        'related_work_order_id',
        'related_work_order_task_id',
        'note',
        'payload_json',
        'created_at',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'created_at' => 'datetime',
    ];

    public function componentInstance(): BelongsTo
    {
        return $this->belongsTo(ComponentInstance::class, 'component_instance_id')->withTrashed();
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function heldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'held_by_user_id');
    }

    public function fromAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'from_asset_id');
    }

    public function toAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'to_asset_id');
    }

    public function fromStorageLocation(): BelongsTo
    {
        return $this->belongsTo(ComponentStorageLocation::class, 'from_storage_location_id');
    }

    public function toStorageLocation(): BelongsTo
    {
        return $this->belongsTo(ComponentStorageLocation::class, 'to_storage_location_id');
    }

    public function relatedWorkOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'related_work_order_id');
    }

    public function relatedWorkOrderTask(): BelongsTo
    {
        return $this->belongsTo(WorkOrderTask::class, 'related_work_order_task_id');
    }

    public function isAutoAgedVerificationEscalation(): bool
    {
        return $this->event_type === 'flagged_needs_verification'
            && (bool) data_get($this->payload_json, 'aged_from_transfer', false);
    }

    public function actionLabel(): string
    {
        if ($this->isAutoAgedVerificationEscalation()) {
            return __('Auto-Flagged Needs Verification');
        }

        return Str::headline($this->event_type);
    }
}
