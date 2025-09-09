<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Watson\Validating\ValidatingTrait;

class Sku extends SnipeModel
{
    use HasFactory;
    use SoftDeletes;
    use ValidatingTrait;

    protected $fillable = ['model_id', 'name'];

    protected $rules = [
        'model_id' => 'required|integer|exists:models,id',
        'name' => 'required|string|max:255',
    ];

    public function model()
    {
        return $this->belongsTo(AssetModel::class, 'model_id');
    }

    public function assets()
    {
        return $this->hasMany(Asset::class, 'sku_id');
    }
}
