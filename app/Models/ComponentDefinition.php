<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ComponentDefinition extends SnipeModel
{
    use HasFactory;
    use SoftDeletes;

    public const PLACEMENT_ASSET_ONLY = 'asset_only';
    public const PLACEMENT_SUBCOMPONENT_ONLY = 'subcomponent_only';
    public const PLACEMENT_EITHER = 'either';

    protected $table = 'component_definitions';

    protected $fillable = [
        'uuid',
        'name',
        'category_id',
        'manufacturer_id',
        'model_number',
        'part_code',
        'spec_summary',
        'metadata_json',
        'serial_tracking_mode',
        'placement_mode',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'metadata_json' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $definition): void {
            if (empty($definition->uuid)) {
                $definition->uuid = (string) Str::uuid();
            }
        });

        static::saving(function (self $definition): void {
            $placementMode = $definition->placement_mode ?? self::PLACEMENT_EITHER;

            if (!in_array($placementMode, self::placementModes(), true)) {
                throw new InvalidArgumentException('Component definition placement mode is invalid.');
            }
        });
    }

    /**
     * @return array<int, string>
     */
    public static function placementModes(): array
    {
        return [
            self::PLACEMENT_ASSET_ONLY,
            self::PLACEMENT_SUBCOMPONENT_ONLY,
            self::PLACEMENT_EITHER,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function assetPlacementModes(): array
    {
        return [
            self::PLACEMENT_ASSET_ONLY,
            self::PLACEMENT_EITHER,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function subcomponentPlacementModes(): array
    {
        return [
            self::PLACEMENT_SUBCOMPONENT_ONLY,
            self::PLACEMENT_EITHER,
        ];
    }

    public function canBeInstalledOnAsset(): bool
    {
        return in_array($this->placement_mode ?? self::PLACEMENT_EITHER, self::assetPlacementModes(), true);
    }

    public function canBeUsedAsSubcomponent(): bool
    {
        return in_array($this->placement_mode ?? self::PLACEMENT_EITHER, self::subcomponentPlacementModes(), true);
    }

    public static function placementModeOptions(): array
    {
        return [
            self::PLACEMENT_ASSET_ONLY => __('Asset Only'),
            self::PLACEMENT_SUBCOMPONENT_ONLY => __('Subcomponent Only'),
            self::PLACEMENT_EITHER => __('Either'),
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class, 'manufacturer_id');
    }

    public function instances(): HasMany
    {
        return $this->hasMany(ComponentInstance::class, 'component_definition_id');
    }

    public function expectedTemplates(): HasMany
    {
        return $this->hasMany(ModelNumberComponentTemplate::class, 'component_definition_id');
    }

    public function subcomponentTemplates(): HasMany
    {
        return $this->hasMany(ComponentDefinitionSubcomponentTemplate::class, 'parent_component_definition_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function usedAsSubcomponentTemplates(): HasMany
    {
        return $this->hasMany(ComponentDefinitionSubcomponentTemplate::class, 'child_component_definition_id');
    }

    public function attributeContributions(): HasMany
    {
        return $this->hasMany(ComponentDefinitionAttribute::class, 'component_definition_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
