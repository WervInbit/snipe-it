<?php

namespace App\Http\Requests;

use App\Models\AttributeDefinition;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AttributeDefinitionVersionRequest extends Request
{
    public function authorize(): bool
    {
        /** @var AttributeDefinition|null $attribute */
        $attribute = $this->route('attribute');

        if (!$attribute instanceof AttributeDefinition) {
            return false;
        }

        return Gate::allows('update', $attribute);
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'datatype' => ['required', 'string', Rule::in(AttributeDefinition::DATATYPES)],
            'unit' => ['nullable', 'string', 'max:50'],
            'required_for_category' => ['sometimes', 'boolean'],
            'allow_custom_values' => ['sometimes', 'boolean'],
            'allow_asset_override' => ['sometimes', 'boolean'],
            'constraints' => ['nullable', 'array'],
            'constraints.min' => ['nullable', 'numeric'],
            'constraints.max' => ['nullable', 'numeric'],
            'constraints.step' => ['nullable', 'numeric', 'min:0'],
            'constraints.regex' => ['nullable', 'string', 'max:255'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'options' => ['nullable', 'array'],
            'options.new' => ['nullable', 'array'],
            'options.new.*.value' => ['required', 'string', 'max:255'],
            'options.new.*.label' => ['required', 'string', 'max:255'],
            'options.new.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'options.new.*.active' => ['nullable', 'boolean'],
        ];
    }
}
