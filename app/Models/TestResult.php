<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestResult extends Model
{
    protected $fillable = ['test_run_id', 'test_type_id', 'status', 'notes'];

    public function run()
    {
        return $this->belongsTo(TestRun::class, 'test_run_id');
    }

    public function type()
    {
        return $this->belongsTo(TestType::class, 'test_type_id');
    }
}
