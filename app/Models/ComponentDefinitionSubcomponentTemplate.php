<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComponentDefinitionSubcomponentTemplate extends SnipeModel
{
    use HasFactory;

    protected $table = 'component_definition_subcomponent_templates';

    protected $fillable = [
        'parent_component_definition_id',
        'child_component_definition_id',
        'expected_name',
        'expected_qty',
        'is_required',
        'sort_order',
        'metadata_json',
        'notes',
    ];

    protected $casts = [
        'parent_component_definition_id' => 'integer',
        'child_component_definition_id' => 'integer',
        'expected_qty' => 'integer',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
        'metadata_json' => 'array',
    ];

    public function parentComponentDefinition(): BelongsTo
    {
        return $this->belongsTo(ComponentDefinition::class, 'parent_component_definition_id');
    }

    public function childComponentDefinition(): BelongsTo
    {
        return $this->belongsTo(ComponentDefinition::class, 'child_component_definition_id');
    }

    public function expectedStates(): HasMany
    {
        return $this->hasMany(ComponentExpectedSubcomponentState::class, 'component_definition_subcomponent_template_id');
    }
}
