<?php

namespace App\Http\Requests\TestType;

use App\Models\TestType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateTestTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var TestType $testtype */
        $testtype = $this->route('testtype');

        return $this->user()->can('update', $testtype);
    }

    public function rules(): array
    {
        /** @var TestType $testtype */
        $testtype = $this->route('testtype');

        return [
            'name' => ['required', 'string', 'max:191'],
            'slug' => [
                'nullable',
                'string',
                'max:191',
                'regex:/^[A-Za-z0-9-_]+$/',
                Rule::unique('test_types', 'slug')->ignore($testtype->id),
            ],
            'attribute_definition_id' => ['nullable', 'exists:attribute_definitions,id'],
            'instructions' => ['nullable', 'string'],
            'tooltip' => ['nullable', 'string'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'is_required' => ['sometimes', 'boolean'],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return $data;
    }
}
