<?php

namespace App\Modules\Sector\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class StoreSectorRequest extends BaseFormRequest
{
    public function rules()
    {
        return [
            'municipality_id' => 'required|integer|exists:municipalities,id',
            'name' => 'required|string|max:255',
            'geojson_polygon' => 'nullable|array',
        ];
    }

    public function messages()
    {
        return [];
    }
}
