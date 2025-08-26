<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestType extends Model
{
    protected $fillable = ['name', 'description'];

    public function results()
    {
        return $this->hasMany(TestResult::class);
    }
}
