<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetTest extends SnipeModel
{
    use HasFactory;
    use SoftDeletes;
    use Loggable;

    protected $table = 'asset_tests';

    protected $fillable = [
        'asset_id',
        'performed_at',
        'status',
        'needs_cleaning',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
        'needs_cleaning' => 'boolean',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }
}
