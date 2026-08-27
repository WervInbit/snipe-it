<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LicenseCheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'notes' => ['nullable', 'string'],
            'asset_id' => [
                'nullable',
                'required_without:assigned_to',
                'prohibits:assigned_to',
                'integer',
                'exists:assets,id,deleted_at,NULL',
            ],
            'assigned_to' => [
                'nullable',
                'required_without:asset_id',
                'prohibits:asset_id',
                'integer',
                'exists:users,id,deleted_at,NULL',
            ],
        ];
    }
}
