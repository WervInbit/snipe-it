<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusLabelAccessRule extends Model
{
    public const SUBJECT_USER = 'user';
    public const SUBJECT_GROUP = 'group';

    public const INHERIT = 0;
    public const ALLOW = 1;
    public const DENY = -1;

    protected $fillable = [
        'status_label_id',
        'subject_type',
        'subject_id',
        'view_value',
        'select_value',
    ];

    protected $casts = [
        'view_value' => 'integer',
        'select_value' => 'integer',
    ];

    public function statusLabel(): BelongsTo
    {
        return $this->belongsTo(Statuslabel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'subject_id');
    }
}
