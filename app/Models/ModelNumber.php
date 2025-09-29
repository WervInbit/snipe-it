<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModelNumber extends SnipeModel
{
    use HasFactory;

    protected $table = 'model_numbers';

    protected $fillable = [
        'model_id',
        'code',
        'label',
    ];

    protected $casts = [
        'model_id' => 'int',
    ];

    public function model(): BelongsTo
    {
        return $this->belongsTo(AssetModel::class, 'model_id');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ModelNumberAttribute::class, 'model_number_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'model_number_id');
    }
}
