<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\MayContainCustomFields;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Company;
use App\Models\Setting;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\Gate;
use App\Rules\AssetCannotBeCheckedOutToNondeployableStatus;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends ImageUploadRequest
{
    use MayContainCustomFields;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('create', new Asset);
    }

    protected function prepareForValidation(): void
    {
        $this->normaliseCompositeModelSelection();
        // Guard against users passing in an array for company_id instead of an integer.
        // If the company_id is not an integer then we simply use what was
        // provided to be caught by model level validation later.
        // The use of is_numeric accounts for 1 and '1'.
        $idForCurrentUser = is_numeric($this->company_id)
            ? Company::getIdForCurrentUser($this->company_id)
            : $this->company_id;

        $this->parseLastAuditDate();

        $this->merge([
            'asset_tag' => $this->asset_tag,
            'company_id' => $idForCurrentUser,
        ]);
        parent::prepareForValidation();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $modelRules = (new Asset)->getRules();

        $modelId = $this->input('model_id');
        $model = $modelId
            ? AssetModel::with(['modelNumbers' => function ($query) {
                $query->orderBy('code');
            }])->find((int) $modelId)
            : null;
        $availableModelNumbers = $model?->modelNumbers ?? collect();

        if (Setting::getSettings()->digit_separator === '1.234,56' && is_string($this->input('purchase_cost'))) {
            // If purchase_cost was submitted as a string with a comma separator
            // then we need to ignore the normal numeric rules.
            // Since the original rules still live on the model they will be run
            // right before saving (and after purchase_cost has been
            // converted to a float via setPurchaseCostAttribute).
            $modelRules = $this->removeNumericRulesFromPurchaseCost($modelRules);
        }

        $requireModelNumber = $availableModelNumbers->count() > 1;

        $modelNumberRules = $modelId
            ? array_filter([
                $requireModelNumber ? 'required' : 'nullable',
                'integer',
                Rule::exists('model_numbers', 'id')->where('model_id', (int) $modelId),
            ])
            : ['nullable'];

        return array_merge(
            $modelRules,
            [
                'model_number_id' => $modelNumberRules,
                'status_id' => [new AssetCannotBeCheckedOutToNondeployableStatus()],
                'attribute_overrides' => ['nullable', 'array'],
                'attribute_overrides.*' => ['nullable'],
            ],
            parent::rules(),
        );
    }

    private function parseLastAuditDate(): void
    {
        if ($this->input('last_audit_date')) {
            try {
                $lastAuditDate = Carbon::parse($this->input('last_audit_date'));

                $this->merge([
                    'last_audit_date' => $lastAuditDate->startOfDay()->format('Y-m-d H:i:s'),
                ]);
            } catch (InvalidFormatException $e) {
                // we don't need to do anything here...
                // we'll keep the provided date in an
                // invalid format so validation picks it up later
            }
        }
    }

    private function removeNumericRulesFromPurchaseCost(array $rules): array
    {
        $purchaseCost = $rules['purchase_cost'];

        // If rule is in "|" format then turn it into an array
        if (is_string($purchaseCost)) {
            $purchaseCost = explode('|', $purchaseCost);
        }

        $rules['purchase_cost'] = array_filter($purchaseCost, function ($rule) {
            return $rule !== 'numeric' && $rule !== 'gte:0';
        });

        return $rules;
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
