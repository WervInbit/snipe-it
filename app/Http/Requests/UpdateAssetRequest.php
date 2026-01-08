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

        if ($this->shouldAllowDuplicateSerial()) {
            $rules['serial'] = $this->stripSerialUniqueness($rules['serial'] ?? []);
        }

        $modelId = $this->input('model_id') ?: ($this->asset?->model_id);
        $model = $modelId ? AssetModel::find((int) $modelId) : null;
        $activeModelNumbers = $model
            ? $model->modelNumbers()->active()->orderBy('label')->orderBy('code')->get()
            : collect();
        $availableModelNumbers = $activeModelNumbers->values();
        $currentModelNumberId = $this->asset?->model_number_id;
        if ($currentModelNumberId && $model) {
            $currentModelNumber = $model->modelNumbers()->whereKey($currentModelNumberId)->first();
            if ($currentModelNumber && $availableModelNumbers->doesntContain(fn ($number) => $number->id === $currentModelNumber->id)) {
                $availableModelNumbers->push($currentModelNumber);
            }
        }
        $requireModelNumber = $activeModelNumbers->count() > 1;

        $rules['model_number_id'] = $modelId
            ? array_filter([
                $requireModelNumber ? 'required' : 'nullable',
                'integer',
                Rule::exists('model_numbers', 'id')->where(function ($query) use ($modelId, $currentModelNumberId) {
                    $query->where('model_id', (int) $modelId)
                        ->where(function ($nested) use ($currentModelNumberId) {
                            $nested->whereNull('deprecated_at');
                            if ($currentModelNumberId) {
                                $nested->orWhere('id', $currentModelNumberId);
                            }
                        });
                }),
            ])
            : ['nullable'];

        $rules['attribute_overrides'] = ['nullable', 'array'];
        $rules['attribute_overrides.*'] = ['nullable'];
        $rules['status_change_note'] = ['nullable', 'string', 'max:65535'];

        return $rules;
    }

    private function shouldAllowDuplicateSerial(): bool
    {
        if ($this->boolean('allow_duplicate_serial')) {
            return true;
        }

        $flags = (array) $this->input('allow_duplicate_serials', []);
        foreach ($flags as $flag) {
            if (filter_var($flag, FILTER_VALIDATE_BOOLEAN)) {
                return true;
            }
        }

        return false;
    }

    private function stripSerialUniqueness(array|string $rules): array
    {
        $rulesArray = is_array($rules) ? $rules : explode('|', $rules);

        return array_values(array_filter($rulesArray, function ($rule) {
            return !str_starts_with((string) $rule, 'unique_undeleted:assets,serial');
        }));
    }

    protected function prepareForValidation(): void
    {
        $this->normaliseCompositeModelSelection();
        parent::prepareForValidation();
    }

    private function normaliseCompositeModelSelection(): void
    {
        $composite = $this->input('model_id_selector');

        if (!$composite) {
            return;
        }

        $modelId = null;
        $modelNumberId = null;

        if (is_numeric($composite)) {
            $modelId = (int) $composite;
        } elseif (is_string($composite) && str_contains($composite, ':')) {
            [$rawModel, $rawNumber] = array_pad(explode(':', $composite, 2), 2, null);
            if ($rawModel !== null && $rawModel !== '') {
                $modelId = (int) $rawModel;
            }
            if ($rawNumber !== null && $rawNumber !== '') {
                $modelNumberId = (int) $rawNumber;
            }
        }

        $payload = [];

        if ($modelId !== null) {
            $payload['model_id'] = $modelId;
        }

        if ($modelNumberId !== null) {
            $payload['model_number_id'] = $modelNumberId;
        }

        if (!empty($payload)) {
            $this->merge($payload);
        }
    }
}
