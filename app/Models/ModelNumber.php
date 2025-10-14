<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\HasUploads;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ModelNumber extends SnipeModel
{
    use HasFactory;
    use HasUploads;

    protected $table = 'model_numbers';

    protected $fillable = [
        'model_id',
        'code',
        'label',
    ];

    protected $casts = [
        'model_id' => 'int',
        'deprecated_at' => 'datetime',
    ];

    public function getNameAttribute(): string
    {
        return $this->label ?: $this->code;
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(AssetModel::class, 'model_id');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ModelNumberAttribute::class, 'model_number_id')->orderBy('display_order')->orderBy('id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'model_number_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deprecated_at');
    }

    public function isDeprecated(): bool
    {
        return $this->deprecated_at !== null;
    }

    public function deprecate(): void
    {
        if ($this->isDeprecated()) {
            return;
        }

        $this->forceFill(['deprecated_at' => Carbon::now()])->save();
    }

    public function restoreStatus(): void
    {
        if (!$this->isDeprecated()) {
            return;
        }

        $this->forceFill(['deprecated_at' => null])->save();
    }
}
