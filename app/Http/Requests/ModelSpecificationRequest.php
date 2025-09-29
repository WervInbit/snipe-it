<?php

namespace App\Http\Requests;

use App\Models\AssetModel;
use Illuminate\Support\Facades\Gate;

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
            'attributes' => ['required', 'array'],
            'attributes.*' => ['nullable'],
        ];
    }
}
