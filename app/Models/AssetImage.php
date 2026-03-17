<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetImage extends Model
{
    protected $fillable = [
        'file_path',
        'caption',
        'sort_order',
        'source',
        'source_photo_id',
    ];

    protected $casts = [
        'sort_order' => 'int',
        'source_photo_id' => 'int',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function sourcePhoto(): BelongsTo
    {
        return $this->belongsTo(TestResultPhoto::class, 'source_photo_id');
    }
}
