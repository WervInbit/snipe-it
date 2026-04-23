<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WorkOrder extends SnipeModel
{
    use HasFactory;
    use CompanyableTrait;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_INTAKE = 'intake';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_READY_FOR_PICKUP = 'ready_for_pickup';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const VISIBILITY_PROFILE_FULL = 'full';
    public const VISIBILITY_PROFILE_BASIC = 'basic';
    public const VISIBILITY_PROFILE_CUSTOM = 'custom';

    protected $table = 'work_orders';

    protected $fillable = [
        'uuid',
        'work_order_number',
        'company_id',
        'primary_contact_user_id',
        'title',
        'description',
        'status',
        'priority',
        'visibility_profile',
        'portal_visibility_json',
        'intake_date',
        'due_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'portal_visibility_json' => 'array',
        'intake_date' => 'date',
        'due_date' => 'date',
    ];

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => __('Draft'),
            self::STATUS_INTAKE => __('Intake'),
            self::STATUS_IN_PROGRESS => __('In Progress'),
            self::STATUS_BLOCKED => __('Blocked'),
            self::STATUS_READY_FOR_PICKUP => __('Ready for Pickup'),
            self::STATUS_COMPLETED => __('Completed'),
            self::STATUS_CANCELLED => __('Cancelled'),
        ];
    }

    public static function priorityOptions(): array
    {
        return [
            self::PRIORITY_LOW => __('Low'),
            self::PRIORITY_NORMAL => __('Normal'),
            self::PRIORITY_HIGH => __('High'),
            self::PRIORITY_URGENT => __('Urgent'),
        ];
    }

    public static function visibilityProfileOptions(): array
    {
        return [
            self::VISIBILITY_PROFILE_FULL => __('Full'),
            self::VISIBILITY_PROFILE_BASIC => __('Basic'),
            self::VISIBILITY_PROFILE_CUSTOM => __('Custom'),
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $workOrder): void {
            if (empty($workOrder->uuid)) {
                $workOrder->uuid = (string) Str::uuid();
            }

            if (empty($workOrder->work_order_number)) {
                do {
                    $candidate = 'WO-' . now()->format('ymd') . '-' . Str::upper(Str::random(4));
                } while (self::withTrashed()->where('work_order_number', $candidate)->exists());

                $workOrder->work_order_number = $candidate;
            }
        });
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        $user = auth()->user();
        $shouldBypassCompanyScope = request()?->routeIs('account.work-orders.*')
            || ($user && !$user->isSuperUser() && $user->company_id === null);

        if ($shouldBypassCompanyScope) {
            $query->withoutGlobalScope(CompanyableScope::class);
        }

        return parent::resolveRouteBindingQuery($query, $value, $field);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function primaryContact(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_contact_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(WorkOrderAsset::class, 'work_order_id')->orderBy('sort_order')->orderBy('id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(WorkOrderTask::class, 'work_order_id')->orderBy('sort_order')->orderBy('id');
    }

    public function visibleUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'work_order_user_access')
            ->withPivot('granted_by')
            ->withTimestamps();
    }

    public function componentEvents(): HasMany
    {
        return $this->hasMany(ComponentEvent::class, 'related_work_order_id');
    }

    public function isVisibleTo(User $user): bool
    {
        if ($user->isSuperUser() || $user->isAdmin() || $user->hasAccess('workorders.view')) {
            return true;
        }

        if ($this->primary_contact_user_id === $user->id) {
            return true;
        }

        if ($this->visibleUsers()->where('users.id', $user->id)->exists()) {
            return true;
        }

        return $this->company_id !== null
            && $user->company_id !== null
            && $this->company_id === $user->company_id
            && $user->hasAccess('portal.view');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperUser() || $user->isAdmin() || $user->hasAccess('workorders.view')) {
            return $query;
        }

        return $query->where(function (Builder $visibleQuery) use ($user): void {
            $visibleQuery->where('primary_contact_user_id', $user->id)
                ->orWhereHas('visibleUsers', function (Builder $userQuery) use ($user): void {
                    $userQuery->where('users.id', $user->id);
                });

            if ($user->hasAccess('portal.view') && $user->company_id !== null) {
                $visibleQuery->orWhere('company_id', $user->company_id);
            }
        });
    }

    public function portalShowsComponents(): bool
    {
        if ($this->visibility_profile === self::VISIBILITY_PROFILE_FULL) {
            return true;
        }

        if ($this->visibility_profile === self::VISIBILITY_PROFILE_BASIC) {
            return false;
        }

        return (bool) data_get($this->portal_visibility_json, 'show_components', false);
    }

    public function portalShowsCustomerNotes(): bool
    {
        if ($this->visibility_profile === self::VISIBILITY_PROFILE_FULL) {
            return true;
        }

        if ($this->visibility_profile === self::VISIBILITY_PROFILE_BASIC) {
            return true;
        }

        return (bool) data_get($this->portal_visibility_json, 'show_notes_customer', false);
    }
}
