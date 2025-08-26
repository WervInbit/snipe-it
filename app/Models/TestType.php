<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class TestType extends SnipeModel
{
    use HasFactory;

    protected $table = 'test_types';

    protected $fillable = [
        'name',
        'slug',
        'tooltip',
    ];

    public function results()
    {
        return $this->hasMany(TestResult::class, 'test_type_id');
    }
}
