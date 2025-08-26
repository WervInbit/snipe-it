<?php

namespace App\Models;

use App\Models\SnipeModel;
use App\Models\Traits\TestAuditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Represents a collection of tests executed on an asset.
 *
 * A test run belongs to an asset and the user who performed it and contains
 * many individual test results and audit records.
 */
class TestRun extends SnipeModel
{
    use HasFactory;
    use TestAuditable;

    protected $table = 'test_runs';

    protected $fillable = [
        'asset_id',
        'user_id',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * Asset the tests were run against.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * User who performed the test run.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Individual results captured during the run.
     */
    public function results(): HasMany
    {
        return $this->hasMany(TestResult::class, 'test_run_id');
    }

    /**
     * Audit log entries for the test run.
     */
    public function audits(): MorphMany
    {
        return $this->morphMany(TestAudit::class, 'auditable');
    }
}
