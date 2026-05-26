<?php

namespace App\Models;

use App\Models\Traits\HasUploads;
use App\Services\ComponentTagGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use InvalidArgumentException;

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

    public const LIFECYCLE_ATTACHED = 'attached';
    public const LIFECYCLE_IN_STOCK = 'in_stock';
    public const LIFECYCLE_IN_TRAY = 'in_tray';
    public const LIFECYCLE_DESTRUCTION_PENDING = 'destruction_pending';
    public const LIFECYCLE_DESTROYED = 'destroyed';
    public const LIFECYCLE_SOLD_RETURNED = 'sold_returned';

    public const CONDITION_UNKNOWN = 'unknown';
    public const CONDITION_GOOD = 'good';
    public const CONDITION_FAIR = 'fair';
    public const CONDITION_POOR = 'poor';
    public const CONDITION_BROKEN = 'broken';

    public const CONDITION_STATUS_GOOD = 'good';
    public const CONDITION_STATUS_NEEDS_ATTENTION = 'needs_attention';
    public const CONDITION_STATUS_DAMAGED = 'damaged';

    public const SOURCE_EXTRACTED = 'extracted';
    public const SOURCE_PURCHASED = 'purchased';
    public const SOURCE_EXTERNAL_INTAKE = 'external_intake';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_EXPECTED_BASELINE = 'expected_baseline';

    public static function sourceTypeOptions(bool $includeInternal = false): array
    {
        $options = [
            self::SOURCE_MANUAL => __('Manual'),
            self::SOURCE_PURCHASED => __('Purchased'),
            self::SOURCE_EXTERNAL_INTAKE => __('External Intake'),
            self::SOURCE_EXTRACTED => __('Extracted'),
        ];

        if ($includeInternal) {
            $options[self::SOURCE_EXPECTED_BASELINE] = __('Expected Baseline');
        }

        return $options;
    }

    public static function sourceTypeLabel(?string $sourceType): ?string
    {
        if ($sourceType === null || $sourceType === '') {
            return null;
        }

        return self::sourceTypeOptions(true)[$sourceType] ?? Str::headline($sourceType);
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_INSTALLED => __('Installed'),
            self::STATUS_IN_STOCK => __('In Stock'),
            self::STATUS_IN_TRANSFER => __('In Tray'),
            self::STATUS_NEEDS_VERIFICATION => __('Needs Verification'),
            self::STATUS_DEFECTIVE => __('Defective'),
            self::STATUS_DESTRUCTION_PENDING => __('Destruction Pending'),
            self::STATUS_DESTROYED_RECYCLED => __('Destroyed / Recycled'),
            self::STATUS_SOLD_RETURNED => __('Sold / Returned'),
        ];
    }

    public static function statusLabel(?string $status): ?string
    {
        if ($status === null || $status === '') {
            return null;
        }

        return self::statusOptions()[$status] ?? Str::headline($status);
    }

    public static function lifecycleStatusOptions(): array
    {
        return [
            self::LIFECYCLE_ATTACHED => __('Attached'),
            self::LIFECYCLE_IN_STOCK => __('In Stock'),
            self::LIFECYCLE_IN_TRAY => __('In Tray'),
            self::LIFECYCLE_DESTRUCTION_PENDING => __('Destruction Pending'),
            self::LIFECYCLE_DESTROYED => __('Destroyed'),
            self::LIFECYCLE_SOLD_RETURNED => __('Sold / Returned'),
        ];
    }

    public static function lifecycleStatusLabel(?string $status): ?string
    {
        if ($status === null || $status === '') {
            return null;
        }

        return self::lifecycleStatusOptions()[$status] ?? Str::headline($status);
    }

    public static function conditionStatusOptions(): array
    {
        return [
            self::CONDITION_STATUS_GOOD => __('Good'),
            self::CONDITION_STATUS_NEEDS_ATTENTION => __('Needs Attention'),
            self::CONDITION_STATUS_DAMAGED => __('Damaged'),
        ];
    }

    public static function conditionStatusLabel(?string $status): ?string
    {
        if ($status === null || $status === '') {
            return null;
        }

        return self::conditionStatusOptions()[$status] ?? Str::headline($status);
    }

    public static function attachmentWarningConditionStatuses(): array
    {
        return [
            self::CONDITION_STATUS_NEEDS_ATTENTION,
            self::CONDITION_STATUS_DAMAGED,
        ];
    }

    public static function attachmentWarningLifecycleStatuses(): array
    {
        return [
            self::LIFECYCLE_SOLD_RETURNED,
        ];
    }

    public static function lifecycleStatusForLegacyStatus(?string $status): string
    {
        return match ($status) {
            self::STATUS_INSTALLED => self::LIFECYCLE_ATTACHED,
            self::STATUS_IN_TRANSFER => self::LIFECYCLE_IN_TRAY,
            self::STATUS_DESTRUCTION_PENDING => self::LIFECYCLE_DESTRUCTION_PENDING,
            self::STATUS_DESTROYED_RECYCLED => self::LIFECYCLE_DESTROYED,
            self::STATUS_SOLD_RETURNED => self::LIFECYCLE_SOLD_RETURNED,
            default => self::LIFECYCLE_IN_STOCK,
        };
    }

    public static function conditionStatusForLegacyState(?string $status, ?string $conditionCode): string
    {
        if ($status === self::STATUS_NEEDS_VERIFICATION) {
            return self::CONDITION_STATUS_NEEDS_ATTENTION;
        }

        if ($status === self::STATUS_DEFECTIVE) {
            return self::CONDITION_STATUS_DAMAGED;
        }

        return match ($conditionCode) {
            self::CONDITION_POOR, self::CONDITION_BROKEN => self::CONDITION_STATUS_DAMAGED,
            self::CONDITION_UNKNOWN => self::CONDITION_STATUS_NEEDS_ATTENTION,
            default => self::CONDITION_STATUS_GOOD,
        };
    }

    public static function legacyStatusForLifecycleStatus(?string $status): string
    {
        return match ($status) {
            self::LIFECYCLE_ATTACHED => self::STATUS_INSTALLED,
            self::LIFECYCLE_IN_TRAY => self::STATUS_IN_TRANSFER,
            self::LIFECYCLE_DESTRUCTION_PENDING => self::STATUS_DESTRUCTION_PENDING,
            self::LIFECYCLE_DESTROYED => self::STATUS_DESTROYED_RECYCLED,
            self::LIFECYCLE_SOLD_RETURNED => self::STATUS_SOLD_RETURNED,
            default => self::STATUS_IN_STOCK,
        };
    }

    public static function legacyConditionCodeForConditionStatus(?string $status): string
    {
        return match ($status) {
            self::CONDITION_STATUS_GOOD => self::CONDITION_GOOD,
            self::CONDITION_STATUS_DAMAGED => self::CONDITION_BROKEN,
            default => self::CONDITION_UNKNOWN,
        };
    }

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
        'lifecycle_status',
        'condition_code',
        'condition_status',
        'source_type',
        'source_asset_id',
        'current_asset_id',
        'parent_component_instance_id',
        'root_asset_id',
        'is_materialized_expected',
        'materialized_reason',
        'ancestry_parent_component_instance_id',
        'ancestry_attached_through_at',
        'ancestry_attached_through_event_id',
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
        'component_definition_id' => 'integer',
        'company_id' => 'integer',
        'source_asset_id' => 'integer',
        'current_asset_id' => 'integer',
        'parent_component_instance_id' => 'integer',
        'root_asset_id' => 'integer',
        'is_materialized_expected' => 'boolean',
        'ancestry_parent_component_instance_id' => 'integer',
        'ancestry_attached_through_event_id' => 'integer',
        'storage_location_id' => 'integer',
        'held_by_user_id' => 'integer',
        'metadata_json' => 'array',
        'transfer_started_at' => 'datetime',
        'needs_verification_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'ancestry_attached_through_at' => 'datetime',
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

            $rawDisplayName = $instance->getRawOriginal('display_name')
                ?? ($instance->attributes['display_name'] ?? null);

            if (blank($rawDisplayName)) {
                $definition = $instance->componentDefinition()->first();

                if ($definition) {
                    $instance->display_name = $definition->name;
                }
            }
        });

        static::saving(function (self $instance): void {
            $instance->normalizeLifecycleAndConditionFields();
            $instance->normalizeHierarchyFields();
            $instance->assertHierarchyDepthIsAllowed();
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

    public function rootAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'root_asset_id');
    }

    public function parentComponent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_component_instance_id');
    }

    public function childComponents(): HasMany
    {
        return $this->hasMany(self::class, 'parent_component_instance_id')
            ->orderBy('display_name')
            ->orderBy('id');
    }

    public function expectedSubcomponentStates(): HasMany
    {
        return $this->hasMany(ComponentExpectedSubcomponentState::class, 'component_instance_id')
            ->orderBy('component_definition_subcomponent_template_id');
    }

    public function instanceAttributes(): HasMany
    {
        return $this->hasMany(ComponentInstanceAttribute::class, 'component_instance_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function ancestryParentComponent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'ancestry_parent_component_instance_id');
    }

    public function ancestryAttachedThroughEvent(): BelongsTo
    {
        return $this->belongsTo(ComponentEvent::class, 'ancestry_attached_through_event_id');
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

    public function isSubcomponent(): bool
    {
        return filled($this->parent_component_instance_id);
    }

    public function isTopLevelComponent(): bool
    {
        return !$this->isSubcomponent();
    }

    public function effectiveLifecycleStatus(): string
    {
        return $this->lifecycle_status ?: self::lifecycleStatusForLegacyStatus($this->status);
    }

    public function effectiveConditionStatus(): string
    {
        return $this->condition_status ?: self::conditionStatusForLegacyState($this->status, $this->condition_code);
    }

    public function isTerminalLifecycle(): bool
    {
        return in_array($this->effectiveLifecycleStatus(), [
            self::LIFECYCLE_DESTROYED,
            self::LIFECYCLE_SOLD_RETURNED,
        ], true);
    }

    public function requiresConditionWarningForAttachment(): bool
    {
        return in_array($this->effectiveConditionStatus(), self::attachmentWarningConditionStatuses(), true);
    }

    public function requiresLifecycleWarningForAttachment(): bool
    {
        return in_array($this->effectiveLifecycleStatus(), self::attachmentWarningLifecycleStatuses(), true);
    }

    public function scopeInTray($query)
    {
        return $query->where('lifecycle_status', self::LIFECYCLE_IN_TRAY);
    }

    public function scopeHeldBy($query, User|int $user)
    {
        return $query->where('held_by_user_id', $user instanceof User ? $user->id : $user);
    }

    public function scopeNeedsAttention($query)
    {
        return $query->where(function ($attentionQuery): void {
            $attentionQuery
                ->whereIn('condition_status', [
                    self::CONDITION_STATUS_NEEDS_ATTENTION,
                    self::CONDITION_STATUS_DAMAGED,
                ])
                ->orWhereIn('lifecycle_status', [
                    self::LIFECYCLE_IN_TRAY,
                    self::LIFECYCLE_DESTRUCTION_PENDING,
                ]);
        });
    }

    private function normalizeLifecycleAndConditionFields(): void
    {
        if (blank($this->lifecycle_status)) {
            $this->lifecycle_status = self::lifecycleStatusForLegacyStatus($this->status);
        }

        if (blank($this->status)) {
            $this->status = self::legacyStatusForLifecycleStatus($this->lifecycle_status);
        }

        if (blank($this->condition_status)) {
            $this->condition_status = self::conditionStatusForLegacyState($this->status, $this->condition_code);
        }

        if (blank($this->condition_code)) {
            $this->condition_code = self::legacyConditionCodeForConditionStatus($this->condition_status);
        }

        if (!array_key_exists($this->lifecycle_status, self::lifecycleStatusOptions())) {
            throw new InvalidArgumentException('Invalid component lifecycle status.');
        }

        if (!array_key_exists($this->condition_status, self::conditionStatusOptions())) {
            throw new InvalidArgumentException('Invalid component condition status.');
        }
    }

    private function normalizeHierarchyFields(): void
    {
        if ($this->parent_component_instance_id) {
            $parent = $this->parentComponent()->first();

            if ($parent) {
                if (!$this->current_asset_id && $parent->current_asset_id) {
                    $this->current_asset_id = $parent->current_asset_id;
                }

                $parentRootAssetId = $parent->root_asset_id ?: $parent->current_asset_id;
                if (!$this->root_asset_id && $parentRootAssetId) {
                    $this->root_asset_id = $parentRootAssetId;
                }
            }

            return;
        }

        if ($this->current_asset_id && !$this->root_asset_id) {
            $this->root_asset_id = $this->current_asset_id;
        }
    }

    private function assertHierarchyDepthIsAllowed(): void
    {
        if (!$this->parent_component_instance_id) {
            if (!$this->current_asset_id && $this->root_asset_id) {
                throw new InvalidArgumentException('Root asset cannot be set without an attached asset or parent component.');
            }

            if ($this->current_asset_id && $this->root_asset_id && (int) $this->current_asset_id !== (int) $this->root_asset_id) {
                throw new InvalidArgumentException('Top-level component root asset must match the current asset.');
            }

            return;
        }

        if ($this->exists && (int) $this->parent_component_instance_id === (int) $this->getKey()) {
            throw new InvalidArgumentException('A component cannot be attached to itself.');
        }

        $parent = self::query()->whereKey($this->parent_component_instance_id)->first();

        if (!$parent) {
            throw new InvalidArgumentException('Parent component could not be found.');
        }

        if ($parent->parent_component_instance_id) {
            throw new InvalidArgumentException('Component hierarchy is limited to one subcomponent level.');
        }

        if ($this->exists && $this->childComponents()->exists()) {
            throw new InvalidArgumentException('A component with attached child components cannot also become a subcomponent.');
        }

        if ((int) ($this->current_asset_id ?? 0) !== (int) ($parent->current_asset_id ?? 0)) {
            throw new InvalidArgumentException('Child component must share the parent component current asset.');
        }

        $parentRootAssetId = $parent->root_asset_id ?: $parent->current_asset_id;
        if ((int) ($this->root_asset_id ?? 0) !== (int) ($parentRootAssetId ?? 0)) {
            throw new InvalidArgumentException('Child component root asset must match the parent component root asset.');
        }
    }
}
