<?php

namespace App\Models;

use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\ComponentDefinition;
use App\Models\SnipeModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Asset;
use App\Services\Components\AssetComponentRosterRow;
use App\Services\Components\AssetComponentRosterService;
use App\Services\ModelAttributes\EffectiveAttributeResolver;
use Illuminate\Support\Str;

/**
 * Defines a workflow item that can be executed.
 *
 * Workflow items describe reusable checks/tasks and are referenced by
 * many workflow results.
 */
class TestType extends SnipeModel
{
    use HasFactory;

    protected $table = 'workflow_items';

    protected $fillable = [
        'name',
        'slug',
        'display_order',
        'attribute_definition_id',
        'tooltip',
        'instructions',
        'category',
        'applies_to_all',
        'is_required',
        'result_label_mode',
    ];

    protected $casts = [
        'display_order' => 'int',
        'attribute_definition_id' => 'int',
        'applies_to_all' => 'bool',
        'is_required' => 'bool',
    ];

    public static function normalizeSlugSource(?string $value): string
    {
        $slug = Str::slug((string) $value);

        return $slug !== '' ? $slug : 'workflow-item';
    }

    public static function generateUniqueSlug(?string $value, ?int $ignoreId = null): string
    {
        $baseSlug = static::normalizeSlugSource($value);
        $candidate = $baseSlug;
        $suffix = 2;

        while (static::query()
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    public static function slugUsesAutomaticPattern(?string $name, ?string $slug): bool
    {
        $slug = trim((string) $slug);
        if ($slug === '') {
            return true;
        }

        $baseSlug = static::normalizeSlugSource($name);

        return $slug === $baseSlug
            || preg_match('/^' . preg_quote($baseSlug, '/') . '-\d+$/', $slug) === 1;
    }

    public static function forAttribute(AttributeDefinition $definition): self
    {
        $test = static::query()
            ->where('attribute_definition_id', $definition->id)
            ->first();

        if (!$test) {
            throw new \RuntimeException(sprintf(
                'No test type configured for attribute [%s] (%d).',
                $definition->key,
                $definition->id
            ));
        }

        return $test;
    }

    /**
     * Attribute the test belongs to (optional).
     */
    public function attributeDefinition(): BelongsTo
    {
        return $this->belongsTo(AttributeDefinition::class, 'attribute_definition_id');
    }

    /**
     * Results that have been recorded for this test type.
     */
    public function results(): HasMany
    {
        return $this->hasMany(TestResult::class, 'workflow_item_id');
    }

    /**
     * Categories this test applies to.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_workflow_item', 'workflow_item_id', 'category_id');
    }

    /**
     * Component categories this item applies to.
     */
    public function componentCategories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'component_category_workflow_item', 'workflow_item_id', 'category_id');
    }

    /**
     * Component definitions this item applies to.
     */
    public function componentDefinitions(): BelongsToMany
    {
        return $this->belongsToMany(ComponentDefinition::class, 'component_definition_workflow_item', 'workflow_item_id', 'component_definition_id');
    }

    public function profileItems(): HasMany
    {
        return $this->hasMany(WorkflowProfileItem::class, 'workflow_item_id');
    }

    /**
     * Scope test types for the provided asset and its model/category.
     *
     * Attribute-linked tests apply when the attribute is assigned to the
     * model number. Category selection further scopes those tests if set.
     * Tests without an attribute must be scoped to a category to apply.
     */
    public function scopeForAsset(Builder $query, Asset $asset): Builder
    {
        $asset->loadMissing([
            'model.category',
            'modelNumber.componentTemplates.componentDefinition.category',
            'modelNumber.componentTemplates.componentDefinition.subcomponentTemplates.childComponentDefinition.category',
            'trackedComponents.componentDefinition.category',
        ]);
        $resolver = app(EffectiveAttributeResolver::class);
        $resolved = $resolver->resolveForAsset($asset);

        $attributeIds = $resolved
            ->reject(fn ($attribute) => $attribute->definition->key === Asset::CONDITION_GRADE_ATTRIBUTE_KEY)
            ->map(fn ($attribute) => $attribute->definition->id)
            ->unique()
            ->all();

        $categoryId = $asset->model?->category_id;
        $category = $asset->model?->category;
        $categoryName = $category?->name;
        $categorySlug = $categoryName ? Str::slug($categoryName) : null;
        $categoryType = $category?->category_type;
        [$componentDefinitionIds, $componentCategoryIds] = $this->componentApplicabilityIdsForAsset($asset);

        $matchingIds = static::query()
            ->with(['categories', 'componentDefinitions', 'componentCategories'])
            ->get()
            ->filter(function (TestType $type) use (
                $attributeIds,
                $categoryId,
                $categoryName,
                $categorySlug,
                $categoryType,
                $componentDefinitionIds,
                $componentCategoryIds
            ) {
                $hasAttribute = $type->attribute_definition_id !== null
                    && in_array($type->attribute_definition_id, $attributeIds, true);
                $hasComponentDefinition = $type->componentDefinitions->isNotEmpty()
                    && $type->componentDefinitions->pluck('id')->intersect($componentDefinitionIds)->isNotEmpty();
                $hasComponentCategory = $type->componentCategories->isNotEmpty()
                    && $type->componentCategories->pluck('id')->intersect($componentCategoryIds)->isNotEmpty();

                $categoryMatches = false;

                $legacyCategory = strtolower(trim((string) $type->category));
                $categoryValue = ($legacyCategory !== '' && $legacyCategory !== 'attribute')
                    ? $legacyCategory
                    : null;

                if ($type->categories->isNotEmpty()) {
                    $categoryMatches = $categoryId
                        ? $type->categories->contains('id', $categoryId)
                        : false;
                } elseif ($categoryValue) {
                    $categoryMatches = ($categoryId && is_numeric($categoryValue) && (int) $categoryValue === $categoryId)
                        || ($categoryName && strtolower($categoryName) === $categoryValue)
                        || ($categorySlug && $categorySlug === $categoryValue)
                        || ($categoryType && strtolower((string) $categoryType) === $categoryValue);
                }

                $hasAssetCategoryScope = $type->categories->isNotEmpty() || $categoryValue;
                $hasSpecificSource = $type->applies_to_all
                    || $type->attribute_definition_id !== null
                    || $type->componentDefinitions->isNotEmpty()
                    || $type->componentCategories->isNotEmpty();
                $matchesSpecificSource = $type->applies_to_all
                    || $hasAttribute
                    || $hasComponentDefinition
                    || $hasComponentCategory;

                if ($hasSpecificSource) {
                    if (!$matchesSpecificSource) {
                        return false;
                    }

                    if ($hasAssetCategoryScope) {
                        return $categoryMatches;
                    }

                    return true;
                }

                return $hasAssetCategoryScope && $categoryMatches;
            })
            ->pluck('id')
            ->all();

        if (empty($matchingIds)) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn('id', $matchingIds)->ordered();
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('display_order')
            ->orderBy('name')
            ->orderBy('id');
    }

    /**
     * @return array{0: array<int>, 1: array<int>}
     */
    private function componentApplicabilityIdsForAsset(Asset $asset): array
    {
        $definitions = collect();
        $roster = app(AssetComponentRosterService::class)->buildForAsset($asset);

        foreach ($roster->rows as $row) {
            if (!$row instanceof AssetComponentRosterRow || $row->isRemoved()) {
                continue;
            }

            $definition = $row->component?->componentDefinition
                ?? $row->template?->componentDefinition;

            if (!$definition) {
                continue;
            }

            $definitions->push($definition);

            $stateByTemplate = $row->component
                ? $row->component->expectedSubcomponentStates
                    ->keyBy('component_definition_subcomponent_template_id')
                : collect();

            foreach ($definition->subcomponentTemplates as $subcomponentTemplate) {
                $state = $stateByTemplate->get($subcomponentTemplate->id);
                $remainingQty = max(1, (int) $subcomponentTemplate->expected_qty);

                if ($row->component) {
                    $remainingQty = max(
                        0,
                        $remainingQty
                            - max(0, (int) ($state?->materialized_qty ?? 0))
                            - max(0, (int) ($state?->removed_qty ?? 0))
                    );
                }

                if ($remainingQty > 0 && $subcomponentTemplate->childComponentDefinition) {
                    $definitions->push($subcomponentTemplate->childComponentDefinition);
                }
            }
        }

        return [
            $definitions->pluck('id')->filter()->unique()->values()->all(),
            $definitions->pluck('category_id')->filter()->unique()->values()->all(),
        ];
    }
}
