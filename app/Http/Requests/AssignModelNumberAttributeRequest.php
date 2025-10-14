<?php

namespace App\Http\Requests;

use App\Models\AssetModel;
use Illuminate\Support\Facades\Gate;

class AssignModelNumberAttributeRequest extends Request
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
            'attribute_definition_id' => ['required', 'integer', 'exists:attribute_definitions,id'],
        ];
    }
}
