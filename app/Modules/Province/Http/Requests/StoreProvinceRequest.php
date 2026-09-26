<?php

namespace App\Modules\Province\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class StoreProvinceRequest extends BaseFormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [];
    }
}
