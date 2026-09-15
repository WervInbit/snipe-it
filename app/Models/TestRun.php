<?php

namespace App\Models;

use App\Models\SnipeModel;
use App\Models\Traits\TestAuditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\ModelNumber;

/**
 * Represents a workflow execution on an asset.
 *
 * A workflow run belongs to an asset and the user who performed it and
 * contains many individual workflow results and audit records.
 */
class TestRun extends SnipeModel
{
    use HasFactory;
    use TestAuditable;

    protected $table = 'workflow_runs';

    protected $fillable = [
        'asset_id',
        'model_number_id',
        'workflow_profile_id',
        'profile_name_snapshot',
        'profile_slug_snapshot',
        'profile_display_order_snapshot',
        'readiness_context_hash',
        'prerequisite_snapshot',
        'user_id',
        'guard_override_by',
        'guard_override_reason',
        'guard_override_at',
        'guard_override_details',
        'started_at',
        'finished_at',
    ];

    protected array $auditFields = [
        'asset_id',
        'model_number_id',
        'workflow_profile_id',
        'profile_name_snapshot',
        'profile_slug_snapshot',
        'profile_display_order_snapshot',
        'readiness_context_hash',
        'prerequisite_snapshot',
        'user_id',
        'guard_override_by',
        'guard_override_reason',
        'guard_override_at',
        'guard_override_details',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'profile_display_order_snapshot' => 'int',
        'prerequisite_snapshot' => 'array',
        'guard_override_at' => 'datetime',
        'guard_override_details' => 'array',
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

    public function guardOverrideUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guard_override_by');
    }

    /**
     * A workflow is operationally complete when every required result has an
     * answer. Profiles without required results fall back to all results so a
     * misconfigured profile cannot complete immediately after it is started.
     */
    public function syncFinishedAtFromResults(): void
    {
        $results = $this->results()->get(['is_required', 'status']);
        $requiredResults = $results->where('is_required', true)->values();
        $completionResults = $requiredResults->isNotEmpty() ? $requiredResults : $results;

        $isComplete = $completionResults->isNotEmpty()
            && !$completionResults->contains(
                fn (TestResult $result): bool => $result->status === TestResult::STATUS_NVT
            );

        if ($isComplete && $this->finished_at === null) {
            $this->finished_at = now();
        } elseif (!$isComplete && $this->finished_at !== null) {
            $this->finished_at = null;
        }

        if ($this->isDirty('finished_at')) {
            $this->save();
        }
    }

    public function scopeCurrentFirst(Builder $query): Builder
    {
        return $query->orderByDesc('started_at')->orderByDesc('id');
    }

    /**
     * Model number associated with the asset at the time of the run.
     */
    public function modelNumber(): BelongsTo
    {
        return $this->belongsTo(ModelNumber::class, 'model_number_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(WorkflowProfile::class, 'workflow_profile_id');
    }

    public function getDisplayNameAttribute(): string
    {
        $snapshot = trim((string) $this->profile_name_snapshot);
        if ($snapshot !== '') {
            return $snapshot;
        }

        $profileName = trim((string) optional($this->profile)->name);
        if ($profileName !== '') {
            return $profileName;
        }

        return trans('tests.workflow_run');
    }

    /**
     * Individual results captured during the run.
     */
    public function results(): HasMany
    {
        return $this->hasMany(TestResult::class, 'workflow_run_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Audit log entries for the test run.
     */
    public function audits(): MorphMany
    {
        return $this->morphMany(TestAudit::class, 'auditable');
    }
}
