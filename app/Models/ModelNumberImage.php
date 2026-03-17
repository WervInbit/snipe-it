<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelNumberImage extends Model
{
    protected $fillable = [
        'model_number_id',
        'file_path',
        'caption',
        'sort_order',
    ];

    protected $casts = [
        'model_number_id' => 'int',
        'sort_order' => 'int',
    ];

    public function modelNumber(): BelongsTo
    {
        return $this->belongsTo(ModelNumber::class, 'model_number_id');
    }
}
