<?php

namespace App\Modules\Municipality\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class StoreMunicipalityRequest extends BaseFormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'logo_url' => 'nullable|string|max:255',
            'domain' => 'nullable|string|max:255',
            'subdomain' => 'nullable|string|max:255',
            'province_id' => 'nullable|integer|exists:provinces,id',
            'status' => 'nullable|string|max:255',
            'registration_date' => 'nullable|date',
            'contracted_plan' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [];
    }
}
