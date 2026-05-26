<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComponentExpectedSubcomponentState extends SnipeModel
{
    use HasFactory;

    protected $table = 'component_expected_subcomponent_states';

    protected $fillable = [
        'component_instance_id',
        'component_definition_subcomponent_template_id',
        'removed_qty',
        'materialized_qty',
    ];

    protected $casts = [
        'component_instance_id' => 'integer',
        'component_definition_subcomponent_template_id' => 'integer',
        'removed_qty' => 'integer',
        'materialized_qty' => 'integer',
    ];

    public function componentInstance(): BelongsTo
    {
        return $this->belongsTo(ComponentInstance::class, 'component_instance_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ComponentDefinitionSubcomponentTemplate::class, 'component_definition_subcomponent_template_id');
    }
}
