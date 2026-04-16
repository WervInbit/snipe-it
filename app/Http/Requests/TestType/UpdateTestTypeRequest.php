<?php

namespace App\Http\Requests\TestType;

use App\Models\TestType;
use Illuminate\Foundation\Http\FormRequest;
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
                'regex:/^[A-Za-z0-9-]+$/',
                Rule::unique('test_types', 'slug')->ignore($testtype->id),
            ],
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
        /** @var TestType $testtype */
        $testtype = $this->route('testtype');
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
            'slug' => TestType::generateUniqueSlug($source, $testtype->id),
        ]);
    }
}
