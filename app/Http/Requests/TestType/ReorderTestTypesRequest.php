<?php

namespace App\Http\Requests\TestType;

use App\Http\Requests\Request;
use App\Models\TestType;
use Illuminate\Support\Facades\Gate;

class ReorderTestTypesRequest extends Request
{
    public function authorize(): bool
    {
        return Gate::allows('update', TestType::class);
    }

    public function rules(): array
    {
        return [
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:test_types,id'],
        ];
    }
}

