<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComponentDefinitionAttribute extends SnipeModel
{
    use HasFactory;

    protected $table = 'component_definition_attributes';

    protected $fillable = [
        'component_definition_id',
        'attribute_definition_id',
        'value',
        'raw_value',
        'attribute_option_id',
        'resolves_to_spec',
        'sort_order',
    ];

    protected $casts = [
        'component_definition_id' => 'int',
        'attribute_definition_id' => 'int',
        'attribute_option_id' => 'int',
        'resolves_to_spec' => 'bool',
        'sort_order' => 'int',
    ];

    public function componentDefinition(): BelongsTo
    {
        return $this->belongsTo(ComponentDefinition::class, 'component_definition_id');
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(AttributeDefinition::class, 'attribute_definition_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(AttributeOption::class, 'attribute_option_id');
    }
}
