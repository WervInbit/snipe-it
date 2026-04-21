<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrderAsset extends SnipeModel
{
    use HasFactory;

    protected $table = 'work_order_assets';

    protected $fillable = [
        'work_order_id',
        'asset_id',
        'customer_label',
        'asset_tag_snapshot',
        'serial_snapshot',
        'qr_reference',
        'status',
        'sort_order',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(WorkOrderTask::class, 'work_order_asset_id')->orderBy('sort_order')->orderBy('id');
    }
}
