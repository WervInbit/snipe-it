<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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

    public function testRun(): BelongsTo
    {
        return $this->belongsTo(TestRun::class, 'test_run_id');
    }

    public function testType(): BelongsTo
    {
        return $this->belongsTo(TestType::class, 'test_type_id');
    }

    public function audits(): MorphMany
    {
        return $this->morphMany(TestAudit::class, 'auditable');
    }
}
