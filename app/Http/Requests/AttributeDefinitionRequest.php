<?php

namespace App\Http\Requests;

use App\Models\AttributeDefinition;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AttributeDefinitionRequest extends Request
{
    public function authorize(): bool
    {
        /** @var AttributeDefinition|null $attribute */
        $attribute = $this->route('attribute');

        if ($attribute instanceof AttributeDefinition) {
            return Gate::allows('update', $attribute);
        }

        return Gate::allows('create', AttributeDefinition::class);
    }

    public function withValidator(Validator $validator): void
    {
        /** @var AttributeDefinition|null $attribute */
        $attribute = $this->route('attribute');

        if ($attribute instanceof AttributeDefinition) {
            $validator->after(function (Validator $inner) use ($attribute) {
                if ($this->filled('datatype') && $this->input('datatype') !== $attribute->datatype) {
                    $inner->errors()->add('datatype', __('Datatype is immutable. Create a new version instead.'));
                }

                if ($this->filled('key') && $this->input('key') !== $attribute->key) {
                    $inner->errors()->add('key', __('Key is immutable once created.'));
                }
            });
        }
    }

    public function rules(): array
    {
        /** @var AttributeDefinition|null $attribute */
        $attribute = $this->route('attribute');
        $attributeId = $attribute?->id;

        return [
            'key' => [
                'required',
                'string',
                'min:3',
                'max:100',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('attribute_definitions', 'key')
                    ->ignore($attributeId)
                    ->where(fn ($query) => $query
                        ->whereNull('deleted_at')
                        ->whereNull('deprecated_at')),
            ],
            'label' => ['required', 'string', 'max:255'],
            'datatype' => ['required', 'string', Rule::in(AttributeDefinition::DATATYPES)],
            'unit' => ['nullable', 'string', 'max:50'],
            'required_for_category' => ['sometimes', 'boolean'],
            'needs_test' => ['sometimes', 'boolean'],
            'allow_custom_values' => ['sometimes', 'boolean'],
            'allow_asset_override' => ['sometimes', 'boolean'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'constraints' => ['nullable', 'array'],
            'constraints.min' => ['nullable', 'numeric'],
            'constraints.max' => ['nullable', 'numeric'],
            'constraints.step' => ['nullable', 'numeric', 'min:0'],
            'constraints.regex' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'key.regex' => __('Keys may only contain lowercase letters, numbers, and underscores.'),
        ];
    }
}
