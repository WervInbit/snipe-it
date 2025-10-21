<?php

namespace App\Models;

use App\Models\AttributeDefinition;
use App\Models\SnipeModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'attribute_definition_id',
        'tooltip',
        'instructions',
        'category',
    ];

    protected $casts = [
        'attribute_definition_id' => 'int',
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
     * Scope test types for the provided asset, deriving category via SKU.
     *
     * Future enhancements may map tests directly to a SKU. For now, the
     * asset's SKU (or model) determines its category and filters available
     * tests accordingly.
     */
    public function scopeForAsset(Builder $query, Asset $asset): Builder
    {
        $resolver = app(EffectiveAttributeResolver::class);
        $testIds = $resolver->resolveForAsset($asset)
            ->filter(fn ($attribute) => $attribute->requiresTest)
            ->flatMap(function ($attribute) {
                $definition = $attribute->definition->loadMissing('tests');

                return $definition->tests->pluck('id');
            })
            ->filter()
            ->unique()
            ->all();

        if (empty($testIds)) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn('id', $testIds);
    }
}
