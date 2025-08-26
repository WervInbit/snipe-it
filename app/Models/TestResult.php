<?php

namespace App\Models;

use App\Models\SnipeModel;
use App\Models\Traits\TestAuditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Stores the outcome of a single test within a run.
 *
 * Each result belongs to a specific test run and test type and may have
 * multiple audit records.
 *
 * @property string|null $note Note captured during the test run.
 */
class TestResult extends SnipeModel
{
    use HasFactory;
    use TestAuditable;

    public const STATUS_PASS = 'pass';
    public const STATUS_FAIL = 'fail';
    public const STATUS_NVT  = 'nvt';

    /**
     * Allowed status values for a test result.
     */
    public const STATUSES = [
        self::STATUS_PASS,
        self::STATUS_FAIL,
        self::STATUS_NVT,
    ];

    protected $table = 'test_results';

    protected $fillable = [
        'test_run_id',
        'test_type_id',
        'status',
        'note',
    ];

    public function testRun(): BelongsTo
    {
        return $this->belongsTo(TestRun::class, 'test_run_id');
    }

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
