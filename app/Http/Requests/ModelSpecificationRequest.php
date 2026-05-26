<?php

namespace App\Http\Requests;

use App\Models\AssetModel;
use App\Models\ComponentDefinition;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ModelSpecificationRequest extends Request
{
    public function authorize(): bool
    {
        /** @var AssetModel|null $model */
        $model = $this->route('model');

        if (!$model instanceof AssetModel) {
            return false;
        }

        return Gate::allows('update', $model);
    }

    public function rules(): array
    {
        return [
            'model_number_id' => ['nullable', 'integer', 'exists:model_numbers,id'],
            'attributes' => ['sometimes', 'array'],
            'attributes.*' => ['nullable'],
            'attribute_order' => ['sometimes', 'array'],
            'attribute_order.*' => ['integer', 'exists:attribute_definitions,id'],
            'component_templates' => ['sometimes', 'array'],
            'component_templates.*.id' => ['nullable', 'integer'],
            'component_templates.*.component_definition_id' => [
                'nullable',
                'integer',
                Rule::exists('component_definitions', 'id')->where(
                    fn ($query) => $query
                        ->where('is_active', true)
                        ->whereIn('placement_mode', ComponentDefinition::assetPlacementModes())
                ),
            ],
            'component_templates.*.expected_qty' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
