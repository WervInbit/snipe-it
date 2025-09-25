<?php

namespace App\Http\Requests;

use App\Models\AttributeDefinition;
use App\Models\AttributeOption;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AttributeOptionRequest extends Request
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
        /** @var AttributeDefinition $attribute */
        $attribute = $this->route('attribute');
        /** @var AttributeOption|null $option */
        $option = $this->route('option');

        return [
            'value' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attribute_options', 'value')
                    ->where(fn ($query) => $query
                        ->where('attribute_definition_id', $attribute->id)
                        ->whereNull('deleted_at'))
                    ->ignore($option?->id),
            ],
            'label' => ['required', 'string', 'max:255'],
            'active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
