<?php

namespace App\Http\Requests\TestType;

use App\Models\TestType;
use Illuminate\Foundation\Http\FormRequest;

class StoreTestTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', TestType::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'slug' => ['nullable', 'string', 'max:191', 'regex:/^[A-Za-z0-9-]+$/', 'unique:test_types,slug'],
            'manual_slug_override' => ['sometimes', 'boolean'],
            'attribute_definition_id' => ['nullable', 'exists:attribute_definitions,id'],
            'instructions' => ['nullable', 'string'],
            'tooltip' => ['nullable', 'string'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'is_required' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $manualOverride = $this->boolean('manual_slug_override');
        $name = trim((string) $this->input('name'));
        $slugInput = trim((string) $this->input('slug'));
        $source = $manualOverride
            ? ($slugInput !== '' ? $slugInput : $name)
            : $name;

        if ($source === '') {
            $this->merge([
                'manual_slug_override' => $manualOverride,
                'slug' => null,
            ]);

            return;
        }

        $this->merge([
            'manual_slug_override' => $manualOverride,
            'slug' => TestType::generateUniqueSlug($source),
        ]);
    }
}
