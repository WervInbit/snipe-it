<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrderTask extends SnipeModel
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_DONE = 'done';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'work_order_tasks';

    protected $fillable = [
        'work_order_id',
        'work_order_asset_id',
        'task_type',
        'title',
        'description',
        'status',
        'customer_visible',
        'customer_status_label',
        'assigned_to',
        'started_at',
        'completed_at',
        'sort_order',
        'notes_internal',
        'notes_customer',
    ];

    protected $casts = [
        'customer_visible' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_IN_PROGRESS => __('In Progress'),
            self::STATUS_BLOCKED => __('Blocked'),
            self::STATUS_DONE => __('Done'),
            self::STATUS_CANCELLED => __('Cancelled'),
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    public function workOrderAsset(): BelongsTo
    {
        return $this->belongsTo(WorkOrderAsset::class, 'work_order_asset_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function componentEvents(): HasMany
    {
        return $this->hasMany(ComponentEvent::class, 'related_work_order_task_id');
    }
}
