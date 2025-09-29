<?php

namespace App\Models;

use App\Models\SnipeModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Asset;
use App\Services\ModelAttributes\EffectiveAttributeResolver;

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
        'tooltip',
        'category',
    ];

    public static function forAttribute(AttributeDefinition $definition): self
    {
        $slug = 'attribute-' . $definition->id;

        return static::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $definition->label,
                'tooltip' => $definition->unit ? __('Expected unit: :unit', ['unit' => $definition->unit]) : null,
                'category' => 'attribute',
            ]
        );
    }

    /**
     * Results that have been recorded for this test type.
     */
    public function results(): HasMany
    {
        return $this->hasMany(TestResult::class, 'test_type_id');
    }

    /**
     * Scope test types for the provided asset, deriving category via SKU.
     *
     * Future enhancements may map tests directly to a SKU. For now, the
     * asset's SKU (or model) determines its category and filters available
     * tests accordingly.
     */
    public function scopeForAsset(Builder $query, Asset $asset): Builder
    {
        $resolver = app(EffectiveAttributeResolver::class);
        $attributeTypeIds = $resolver->resolveForAsset($asset)
            ->filter(fn ($attribute) => $attribute->requiresTest)
            ->map(function ($attribute) {
                return static::forAttribute($attribute->definition)->id;
            })
            ->all();

        if (empty($attributeTypeIds)) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn('id', $attributeTypeIds);
    }
}
