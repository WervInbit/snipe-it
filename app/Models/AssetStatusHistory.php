<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssetStatusHistory extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;
    public const CREATED_AT = null;

    protected $table = 'asset_status_history';

    protected $fillable = [
        'asset_id',
        'old_status_id',
        'new_status_id',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function oldStatus()
    {
        return $this->belongsTo(Statuslabel::class, 'old_status_id');
    }

    public function newStatus()
    {
        return $this->belongsTo(Statuslabel::class, 'new_status_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}

