<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\MayContainCustomFields;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends ImageUploadRequest
{
    use MayContainCustomFields;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Gate::allows('update', $this->asset);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = array_merge(
            parent::rules(),
            (new Asset)->getRules(),
            // this is to overwrite rulesets that include required, and rewrite unique_undeleted
            [
                'model_id'  => ['integer', 'exists:models,id,deleted_at,NULL', 'not_array'],
                'status_id' => ['integer', 'exists:status_labels,id'],
                'asset_tag' => [
                    'min:1', 'max:255', 'not_array',
                    Rule::unique('assets', 'asset_tag')->ignore($this->asset)->withoutTrashed()
                ],
            ],
        );

        $categoryId = $this->input('category_id');

        if (!$categoryId) {
            if ($this->input('model_id')) {
                $model = AssetModel::find((int) $this->input('model_id'));
                $categoryId = $model->category_id ?? null;
            } elseif ($this->asset && $this->asset->model) {
                $categoryId = $this->asset->model->category_id;
            }
        }

        // if the purchase cost is passed in as a string **and** the digit_separator is ',' (as is common in the EU)
        // then we tweak the purchase_cost rule to make it a string
        if (Setting::getSettings()->digit_separator === '1.234,56' && is_string($this->input('purchase_cost'))) {
            $rules['purchase_cost'] = ['nullable', 'string'];
        }

        $modelId = $this->input('model_id') ?: ($this->asset?->model_id);

        $rules['model_number_id'] = $modelId
            ? array_filter([
                'required',
                'integer',
                Rule::exists('model_numbers', 'id')->where('model_id', (int) $modelId),
            ])
            : ['nullable'];

        $rules['attribute_overrides'] = ['nullable', 'array'];
        $rules['attribute_overrides.*'] = ['nullable'];

        return $rules;
    }
}
