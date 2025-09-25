<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Watson\Validating\ValidatingTrait;

class AttributeOption extends SnipeModel
{
    use HasFactory;
    use SoftDeletes;
    use ValidatingTrait;

    protected $table = 'attribute_options';

    protected $fillable = [
        'attribute_definition_id',
        'value',
        'label',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'bool',
        'sort_order' => 'int',
    ];

    protected $rules = [
        'attribute_definition_id' => 'required|exists:attribute_definitions,id',
        'value' => 'required|string|min:1|max:255',
        'label' => 'required|string|min:1|max:255',
        'active' => 'boolean',
        'sort_order' => 'nullable|integer|min:0|max:65535',
    ];

    public function definition(): BelongsTo
    {
        return $this->belongsTo(AttributeDefinition::class, 'attribute_definition_id');
    }
}
