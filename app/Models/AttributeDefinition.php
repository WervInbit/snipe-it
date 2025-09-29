<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Watson\Validating\ValidatingTrait;

class AttributeDefinition extends SnipeModel
{
    use HasFactory;
    use SoftDeletes;
    use ValidatingTrait;

    public const DATATYPE_ENUM = 'enum';
    public const DATATYPE_INT = 'int';
    public const DATATYPE_DECIMAL = 'decimal';
    public const DATATYPE_TEXT = 'text';
    public const DATATYPE_BOOL = 'bool';

    public const DATATYPES = [
        self::DATATYPE_ENUM,
        self::DATATYPE_INT,
        self::DATATYPE_DECIMAL,
        self::DATATYPE_TEXT,
        self::DATATYPE_BOOL,
    ];

    protected $table = 'attribute_definitions';

    protected $fillable = [
        'key',
        'label',
        'datatype',
        'unit',
        'required_for_category',
        'needs_test',
        'allow_custom_values',
        'allow_asset_override',
        'constraints',
    ];

    protected $casts = [
        'required_for_category' => 'bool',
        'needs_test' => 'bool',
        'allow_custom_values' => 'bool',
        'allow_asset_override' => 'bool',
    ];

    protected $rules = [
        'key' => 'required|string|min:3|max:100|regex:/^[a-z0-9_]+$/|unique:attribute_definitions,key,NULL,id,deleted_at,NULL',
        'label' => 'required|string|min:3|max:255',
        'datatype' => 'required|string|in:enum,int,decimal,text,bool',
        'unit' => 'nullable|string|max:50',
        'required_for_category' => 'boolean',
        'needs_test' => 'boolean',
        'allow_custom_values' => 'boolean',
        'allow_asset_override' => 'boolean',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'attribute_definition_category')
            ->withTimestamps();
    }

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class);
    }

    public function modelValues(): HasMany
    {
        return $this->hasMany(ModelNumberAttribute::class);
    }

    public function assetOverrides(): HasMany
    {
        return $this->hasMany(AssetAttributeOverride::class);
    }

    public function scopeForCategory($query, ?int $categoryId)
    {
        if (!$categoryId) {
            return $query;
        }

        return $query->where(function ($q) use ($categoryId) {
            $q->whereDoesntHave('categories')
                ->orWhereHas('categories', fn ($relation) => $relation->where('categories.id', $categoryId));
        });
    }

    public function setKeyAttribute(string $value): void
    {
        $this->attributes['key'] = Str::snake($value);
    }

    public function setConstraintsAttribute($value): void
    {
        if (is_null($value) || $value === '') {
            $this->attributes['constraints'] = null;
            return;
        }

        $clean = Arr::only((array) $value, ['min', 'max', 'step', 'regex']);
        $this->normaliseConstraintValues($clean);

        if (empty(array_filter($clean, fn ($item) => $item !== null && $item !== ''))) {
            $this->attributes['constraints'] = null;
            return;
        }

        $this->attributes['constraints'] = json_encode($clean);
    }

    public function getConstraintsAttribute($value): array
    {
        if (!$value) {
            return [];
        }

        $decoded = is_array($value) ? $value : (json_decode($value, true) ?: []);

        return array_filter(
            Arr::only($decoded, ['min', 'max', 'step', 'regex']),
            fn ($item) => $item !== null && $item !== ''
        );
    }

    private function normaliseConstraintValues(array & $clean): void
    {
        if (array_key_exists('min', $clean) && $clean['min'] !== null && $clean['min'] !== '') {
            $clean['min'] = is_numeric($clean['min']) ? (float) $clean['min'] : null;
        }

        if (array_key_exists('max', $clean) && $clean['max'] !== null && $clean['max'] !== '') {
            $clean['max'] = is_numeric($clean['max']) ? (float) $clean['max'] : null;
        }

        if (array_key_exists('step', $clean) && $clean['step'] !== null && $clean['step'] !== '') {
            $clean['step'] = is_numeric($clean['step']) ? (float) $clean['step'] : null;
        }

        if (array_key_exists('regex', $clean) && is_string($clean['regex'])) {
            $clean['regex'] = trim($clean['regex']);
        }
    }

    public function isEnum(): bool
    {
        return $this->datatype === self::DATATYPE_ENUM;
    }

    public function allowsAssetOverride(): bool
    {
        return $this->allow_asset_override === true;
    }
}