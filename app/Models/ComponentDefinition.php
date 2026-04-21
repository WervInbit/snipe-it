<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ComponentDefinition extends SnipeModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'component_definitions';

    protected $fillable = [
        'uuid',
        'name',
        'category_id',
        'manufacturer_id',
        'model_number',
        'part_code',
        'spec_summary',
        'metadata_json',
        'serial_tracking_mode',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'metadata_json' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $definition): void {
            if (empty($definition->uuid)) {
                $definition->uuid = (string) Str::uuid();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class, 'manufacturer_id');
    }

    public function instances(): HasMany
    {
        return $this->hasMany(ComponentInstance::class, 'component_definition_id');
    }

    public function expectedTemplates(): HasMany
    {
        return $this->hasMany(ModelNumberComponentTemplate::class, 'component_definition_id');
    }
}
