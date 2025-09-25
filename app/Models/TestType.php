<?php

namespace App\Models;

use App\Models\SnipeModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Asset;
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
        $categoryName = $asset->sku?->model?->category?->name
            ?? $asset->model?->category?->name;

        $slug = Str::singular(Str::slug((string) $categoryName));

        if (in_array($slug, ['laptop', 'desktop'])) {
            return $query->where('category', 'computer');
        }

        return $query->whereRaw('0 = 1');
    }
}
