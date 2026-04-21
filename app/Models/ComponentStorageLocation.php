<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComponentStorageLocation extends SnipeModel
{
    use HasFactory;

    public const TYPE_STOCK = 'stock';
    public const TYPE_GENERAL = 'general';
    public const TYPE_DESTRUCTION = 'destruction';
    public const TYPE_VERIFICATION = 'verification';

    protected $table = 'component_storage_locations';

    protected $fillable = [
        'name',
        'code',
        'site_location_id',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function siteLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'site_location_id');
    }

    public function componentInstances(): HasMany
    {
        return $this->hasMany(ComponentInstance::class, 'storage_location_id');
    }
}
