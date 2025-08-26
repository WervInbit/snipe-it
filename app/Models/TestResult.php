<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TestAuditable;

class TestResult extends Model
{
    use TestAuditable;

    protected $fillable = [
        'test_run_id',
        'status',
        'details',
    ];

    public function run()
    {
        return $this->belongsTo(TestRun::class, 'test_run_id');
    }
}
