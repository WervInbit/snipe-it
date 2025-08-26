<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Stores the outcome of a single test within a run.
 *
 * Each result belongs to a specific test run and test type and may have
 * multiple audit records.
 */
class TestResult extends SnipeModel
{
    use HasFactory;

    protected $table = 'test_results';

    protected $fillable = [
        'test_run_id',
        'test_type_id',
        'status',
        'note',
    ];

    /**
     * Test run this result is associated with.
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(TestRun::class, 'test_run_id');
    }

    /**
     * Type of test this result represents.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(TestType::class, 'test_type_id');
    }

    /**
     * Audit log entries for changes to this result.
     */
    public function audits(): MorphMany
    {
        return $this->morphMany(TestAudit::class, 'auditable');
    }
}
