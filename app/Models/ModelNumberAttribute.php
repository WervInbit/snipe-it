<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelNumberAttribute extends SnipeModel
{
    use HasFactory;

    protected $table = 'model_number_attributes';

    protected $fillable = [
        'model_number_id',
        'attribute_definition_id',
        'value',
        'raw_value',
        'attribute_option_id',
        'display_order',
    ];

    protected $casts = [
        'model_number_id' => 'int',
        'attribute_definition_id' => 'int',
        'attribute_option_id' => 'int',
        'display_order' => 'int',
    ];

    protected $attributes = [
        'display_order' => 0,
    ];

    public function modelNumber(): BelongsTo
    {
        return $this->belongsTo(ModelNumber::class, 'model_number_id');
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
