<?php

namespace App\Modules\Project\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;
use App\Models\Project;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends BaseFormRequest
{
    public function rules()
    {
        $municipalityId = $this->municipality_id;

        return [
            'sector_id' => [
                'nullable',
                'integer',
                Rule::exists('sectors', 'id')->when($municipalityId, fn ($q) => $q->where('municipality_id', $municipalityId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(Project::TYPES)],
            'description' => ['nullable', 'string', 'max:5000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'address_text' => ['nullable', 'string', 'max:255'],
            'budget_assigned' => ['nullable', 'numeric', 'min:0'],
            'start_date_planned' => ['nullable', 'date'],
            'end_date_planned' => ['nullable', 'date'],
            'contractor_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'type.required' => 'El tipo es obligatorio.',
            'type.in' => 'El tipo seleccionado no es válido.',
        ];
    }
}
