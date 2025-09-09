<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetImage extends Model
{
    protected $fillable = ['file_path', 'caption'];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
