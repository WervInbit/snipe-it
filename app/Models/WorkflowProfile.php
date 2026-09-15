<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WorkflowProfile extends SnipeModel
{
    use HasFactory;

    public const REPEAT_OVERRIDE_REQUIRED = 'override_required';
    public const REPEAT_NEVER = 'never';

    public const REPEAT_POLICIES = [
        self::REPEAT_OVERRIDE_REQUIRED,
        self::REPEAT_NEVER,
    ];

    public const EXECUTION_OPERATOR = 'operator';
    public const EXECUTION_SENIOR = 'senior';
    public const EXECUTION_SUPERVISOR = 'supervisor';

    public const EXECUTION_LEVELS = [
        self::EXECUTION_OPERATOR,
        self::EXECUTION_SENIOR,
        self::EXECUTION_SUPERVISOR,
    ];

    protected $table = 'workflow_profiles';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'is_default',
        'blocks_sale_readiness',
        'display_order',
        'repeat_policy',
        'execution_level',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'is_default' => 'bool',
        'blocks_sale_readiness' => 'bool',
        'display_order' => 'int',
    ];

    public static function normalizeSlugSource(?string $value): string
    {
        $slug = Str::slug((string) $value);

        return $slug !== '' ? $slug : 'workflow-profile';
    }

    public static function generateUniqueSlug(?string $value, ?int $ignoreId = null): string
    {
        $baseSlug = static::normalizeSlugSource($value);
        $candidate = $baseSlug;
        $suffix = 2;

        while (
            static::query()
                ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    public function items(): HasMany
    {
        return $this->hasMany(WorkflowProfileItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(TestRun::class, 'workflow_profile_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_workflow_profile', 'workflow_profile_id', 'category_id');
    }

    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'workflow_profile_dependencies',
            'workflow_profile_id',
            'prerequisite_workflow_profile_id'
        )->withTimestamps();
    }

    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'workflow_profile_dependencies',
            'prerequisite_workflow_profile_id',
            'workflow_profile_id'
        )->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('display_order')
            ->orderBy('name')
            ->orderBy('id');
    }

    public function scopeForAsset(Builder $query, Asset $asset): Builder
    {
        $asset->loadMissing('model.category');
        $categoryId = $asset->model?->category_id;

        return $query->where(function (Builder $inner) use ($categoryId): void {
            $inner->whereDoesntHave('categories');

            if ($categoryId) {
                $inner->orWhereHas(
                    'categories',
                    fn (Builder $relation) => $relation->where('categories.id', $categoryId)
                );
            }
        });
    }

    public static function defaultForAsset(Asset $asset): ?self
    {
        return static::query()
            ->active()
            ->forAsset($asset)
            ->whereHas('items')
            ->orderByDesc('is_default')
            ->ordered()
            ->first();
    }

    public function executionAbility(): string
    {
        return match ($this->execution_level) {
            self::EXECUTION_SUPERVISOR => 'tests.execute.supervisor',
            self::EXECUTION_SENIOR => 'tests.execute.senior',
            default => 'tests.execute',
        };
    }

    public function canBeExecutedBy(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->hasAccess('tests.execute')) {
            return false;
        }

        return match ($this->execution_level) {
            self::EXECUTION_SUPERVISOR => $user->hasAccess('tests.execute.supervisor'),
            self::EXECUTION_SENIOR => $user->hasAccess('tests.execute.senior')
                || $user->hasAccess('tests.execute.supervisor'),
            default => true,
        };
    }
}
