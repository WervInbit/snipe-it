<?php

namespace App\Models;

use App\Models\SnipeModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'description',
    ];

    /**
     * Results that have been recorded for this test type.
     */
    public function results(): HasMany
    {
        return $this->hasMany(TestResult::class, 'test_type_id');
    }
}
