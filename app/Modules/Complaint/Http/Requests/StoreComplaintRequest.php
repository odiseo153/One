<?php

namespace App\Modules\Complaint\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;
use App\Models\Complaint;
use Illuminate\Validation\Rule;

class StoreComplaintRequest extends BaseFormRequest
{
    public function rules()
    {
        return [
            'municipality_id' => [
                'required',
                'integer',
                Rule::exists('municipalities', 'id')->where('status', 'active'),
            ],
            'sector_id' => ['nullable', 'integer', 'exists:sectors,id'],
            'category' => ['required', Rule::in(Complaint::CATEGORIES)],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'address_text' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'citizen_name' => ['required', 'string', 'max:255'],
            'citizen_phone' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages()
    {
        return [];
    }
}
