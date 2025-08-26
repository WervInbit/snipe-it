<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TestAuditable;

class TestRun extends Model
{
    use TestAuditable;

    protected $fillable = [
        'name',
        'notes',
    ];

    public function results()
    {
        return $this->hasMany(TestResult::class);
    }
}
