<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModelNumberComponentTemplate extends SnipeModel
{
    use HasFactory;

    protected $table = 'model_number_component_templates';

    protected $fillable = [
        'model_number_id',
        'component_definition_id',
        'expected_name',
        'slot_name',
        'expected_qty',
        'is_required',
        'sort_order',
        'metadata_json',
        'notes',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'metadata_json' => 'array',
    ];

    public function modelNumber(): BelongsTo
    {
        return $this->belongsTo(ModelNumber::class, 'model_number_id');
    }

    public function componentDefinition(): BelongsTo
    {
        return $this->belongsTo(ComponentDefinition::class, 'component_definition_id');
    }

    public function assetStates(): HasMany
    {
        return $this->hasMany(AssetExpectedComponentState::class, 'model_number_component_template_id');
    }
}
