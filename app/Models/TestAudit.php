<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TestAudit extends SnipeModel
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'test_audits';

    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'user_id',
        'field',
        'before',
        'after',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');

    }
}
