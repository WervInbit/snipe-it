<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelNumberAttribute extends SnipeModel
{
    use HasFactory;

    protected $table = 'model_number_attributes';

    protected $fillable = [
        'model_id',
        'attribute_definition_id',
        'value',
        'raw_value',
        'attribute_option_id',
    ];

    protected $casts = [
        'model_id' => 'int',
        'attribute_definition_id' => 'int',
        'attribute_option_id' => 'int',
    ];

    public function model(): BelongsTo
    {
        return $this->belongsTo(AssetModel::class, 'model_id');
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
