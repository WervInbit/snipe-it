<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestResultPhoto extends Model
{
    use HasFactory;

    protected $table = 'workflow_result_photos';

    protected $fillable = [
        'workflow_result_id',
        'path',
    ];

    public function testResult(): BelongsTo
    {
        return $this->belongsTo(TestResult::class, 'workflow_result_id');
    }
}
