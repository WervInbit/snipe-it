<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAttributeOverride extends SnipeModel
{
    use HasFactory;

    protected $table = 'asset_attribute_overrides';

    protected $fillable = [
        'asset_id',
        'attribute_definition_id',
        'value',
        'raw_value',
        'attribute_option_id',
    ];

    protected $casts = [
        'asset_id' => 'int',
        'attribute_definition_id' => 'int',
        'attribute_option_id' => 'int',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
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
