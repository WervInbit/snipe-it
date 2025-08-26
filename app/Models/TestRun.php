<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestRun extends Model
{
    protected $fillable = ['asset_id', 'user_id'];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function results()
    {
        return $this->hasMany(TestResult::class);
    }
}
