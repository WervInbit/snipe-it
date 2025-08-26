<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestAudit extends Model
{
    protected $fillable = [
        'testable_type',
        'testable_id',
        'event',
        'before',
        'after',
        'actor_id',
    ];

    protected $casts = [
        'before' => 'array',
        'after'  => 'array',
    ];

    public function testable()
    {
        return $this->morphTo();
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
