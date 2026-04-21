<?php

namespace App\Models;

use App\Models\Traits\HasUploads;
use App\Services\ComponentTagGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ComponentInstance extends SnipeModel
{
    use HasFactory;
    use CompanyableTrait;
    use HasUploads;
    use Loggable;
    use SoftDeletes;

    public const STATUS_INSTALLED = 'installed';
    public const STATUS_IN_STOCK = 'in_stock';
    public const STATUS_IN_TRANSFER = 'in_transfer';
    public const STATUS_NEEDS_VERIFICATION = 'needs_verification';
    public const STATUS_DEFECTIVE = 'defective';
    public const STATUS_DESTRUCTION_PENDING = 'destruction_pending';
    public const STATUS_DESTROYED_RECYCLED = 'destroyed_recycled';
    public const STATUS_SOLD_RETURNED = 'sold_returned';

    public const CONDITION_UNKNOWN = 'unknown';
    public const CONDITION_GOOD = 'good';
    public const CONDITION_FAIR = 'fair';
    public const CONDITION_POOR = 'poor';
    public const CONDITION_BROKEN = 'broken';

    public const SOURCE_EXTRACTED = 'extracted';
    public const SOURCE_PURCHASED = 'purchased';
    public const SOURCE_EXTERNAL_INTAKE = 'external_intake';
    public const SOURCE_MANUAL = 'manual';

    protected $table = 'component_instances';

    protected $fillable = [
        'uuid',
        'component_tag',
        'qr_uid',
        'component_definition_id',
        'company_id',
        'display_name',
        'serial',
        'status',
        'condition_code',
        'source_type',
        'source_asset_id',
        'current_asset_id',
        'storage_location_id',
        'held_by_user_id',
        'transfer_started_at',
        'needs_verification_at',
        'last_verified_at',
        'installed_as',
        'supplier_id',
        'purchase_cost',
        'received_at',
        'destroyed_at',
        'metadata_json',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'metadata_json' => 'array',
        'transfer_started_at' => 'datetime',
        'needs_verification_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'received_at' => 'datetime',
        'destroyed_at' => 'datetime',
        'purchase_cost' => 'decimal:4',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $instance): void {
            if (empty($instance->uuid)) {
                $instance->uuid = (string) Str::uuid();
            }

            if (empty($instance->component_tag)) {
                $instance->component_tag = app(ComponentTagGenerator::class)->generate();
            }

            if (empty($instance->qr_uid)) {
                $instance->qr_uid = (string) Str::uuid();
            }

            if (empty($instance->display_name) && $instance->componentDefinition) {
                $instance->display_name = $instance->componentDefinition->name;
            }
        });
    }

    public function componentDefinition(): BelongsTo
    {
        return $this->belongsTo(ComponentDefinition::class, 'component_definition_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function sourceAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'source_asset_id');
    }

    public function currentAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'current_asset_id');
    }

    public function storageLocation(): BelongsTo
    {
        return $this->belongsTo(ComponentStorageLocation::class, 'storage_location_id');
    }

    public function heldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'held_by_user_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ComponentEvent::class, 'component_instance_id')->orderByDesc('created_at');
    }

    public function getNameAttribute(): string
    {
        return $this->display_name ?: $this->component_tag;
    }

    public function getDisplayNameAttribute(): string
    {
        $value = $this->getRawOriginal('display_name');

        if (filled($value)) {
            return $value;
        }

        return $this->componentDefinition?->name
            ?: $this->getRawOriginal('component_tag')
            ?: $this->attributes['component_tag']
            ?? '';
    }

    public function scopeInTray($query)
    {
        return $query->where('status', self::STATUS_IN_TRANSFER);
    }

    public function scopeHeldBy($query, User|int $user)
    {
        return $query->where('held_by_user_id', $user instanceof User ? $user->id : $user);
    }

    public function scopeNeedsAttention($query)
    {
        return $query->whereIn('status', [
            self::STATUS_IN_TRANSFER,
            self::STATUS_NEEDS_VERIFICATION,
            self::STATUS_DESTRUCTION_PENDING,
        ]);
    }
}
