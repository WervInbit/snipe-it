<?php

namespace App\Http\Requests;

use App\Models\AssetModel;
use Illuminate\Support\Facades\Gate;

class ReorderModelNumberAttributesRequest extends Request
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
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ];
    }
}
