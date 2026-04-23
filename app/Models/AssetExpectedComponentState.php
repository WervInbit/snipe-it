<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetExpectedComponentState extends SnipeModel
{
    use HasFactory;

    protected $table = 'asset_expected_component_states';

    protected $fillable = [
        'asset_id',
        'model_number_component_template_id',
        'removed_qty',
    ];

    protected $casts = [
        'asset_id' => 'int',
        'model_number_component_template_id' => 'int',
        'removed_qty' => 'int',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ModelNumberComponentTemplate::class, 'model_number_component_template_id');
    }
}
