<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetStatusEvent extends Model
{
    protected $fillable = [
        'asset_id',
        'from_status_id',
        'to_status_id',
        'triggered_by',
        'note',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function fromStatus(): BelongsTo
    {
        return $this->belongsTo(Statuslabel::class, 'from_status_id');
    }

    public function toStatus(): BelongsTo
    {
        return $this->belongsTo(Statuslabel::class, 'to_status_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
