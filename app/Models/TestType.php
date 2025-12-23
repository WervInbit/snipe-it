<?php

namespace App\Models;

use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\SnipeModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Asset;
use App\Services\ModelAttributes\EffectiveAttributeResolver;
use Illuminate\Support\Str;

/**
 * Defines a kind of diagnostic test that can be executed.
 *
 * Test types describe how a test should be interpreted and are referenced by
 * many test results.
 */
class TestType extends SnipeModel
{
    use HasFactory;

    protected $table = 'test_types';

    protected $fillable = [
        'name',
        'slug',
        'attribute_definition_id',
        'tooltip',
        'instructions',
        'category',
        'is_required',
    ];

    protected $casts = [
        'attribute_definition_id' => 'int',
        'is_required' => 'bool',
    ];

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
        return $this->hasMany(TestResult::class, 'test_type_id');
    }

    /**
     * Categories this test applies to.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_test_type');
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
        $asset->loadMissing('model.category', 'modelNumber');
        $resolver = app(EffectiveAttributeResolver::class);
        $resolved = $resolver->resolveForAsset($asset);

        $attributeIds = $resolved
            ->map(fn ($attribute) => $attribute->definition->id)
            ->unique()
            ->all();

        $categoryId = $asset->model?->category_id;
        $category = $asset->model?->category;
        $categoryName = $category?->name;
        $categorySlug = $categoryName ? Str::slug($categoryName) : null;
        $categoryType = $category?->category_type;

        $matchingIds = static::query()
            ->with('categories')
            ->get()
            ->filter(function (TestType $type) use ($attributeIds, $categoryId, $categoryName, $categorySlug, $categoryType) {
                $hasAttribute = $type->attribute_definition_id !== null
                    && in_array($type->attribute_definition_id, $attributeIds, true);

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

                if ($type->attribute_definition_id !== null) {
                    if (!$hasAttribute) {
                        return false;
                    }

                    if ($type->categories->isNotEmpty() || $categoryValue) {
                        return $categoryMatches;
                    }

                    return true;
                }

                return $categoryMatches;
            })
            ->pluck('id')
            ->all();

        if (empty($matchingIds)) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn('id', $matchingIds);
    }
}
