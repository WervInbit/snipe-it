<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    use HasFactory;

    public const SUPPORTED_TYPES = [
        'accessory',
        'asset',
        'assetModel',
        'category',
        'component',
        'consumable',
        'license',
        'location',
        'manufacturer',
        'supplier',
        'user',
    ];

    protected $casts = [
        'header_row' => 'array',
        'first_row' => 'array',
        'field_map' => 'json',
    ];

    /**
     * Establishes the license -> admin user relationship
     *
     * @author A. Gianotto <snipe@snipe.net>
     * @since  [v2.0]
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    public function adminuser()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperUser()) {
            return $query;
        }

        return $query->where($this->qualifyColumn('created_by'), $user->id);
    }
}
