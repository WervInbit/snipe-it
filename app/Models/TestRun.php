<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TestRun extends SnipeModel
{
    use HasFactory;

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

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(TestResult::class, 'test_run_id');
    }

    public function audits(): MorphMany
    {
        return $this->morphMany(TestAudit::class, 'auditable');
    }
}
