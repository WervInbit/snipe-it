<?php

namespace App\Models;

use App\Models\SnipeModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Asset;

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

    /**
     * Results that have been recorded for this test type.
     */
    public function results(): HasMany
    {
        return $this->hasMany(TestResult::class, 'test_type_id');
    }

    /**
     * Scope test types for the provided asset.
     *
     * This acts as an extension point for future SKU or model-based filtering.
     * For now it simply returns all test types so every device gets the full set
     * and technicians can mark not-applicable tests as needed.
     */
    public function scopeForAsset(Builder $query, Asset $asset): Builder
    {
        return $query;
    }
}
