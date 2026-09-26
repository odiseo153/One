<?php

namespace App\Modules\User\Http\Resources;

use App\Modules\Municipality\Http\Resources\MunicipalityResource;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'municipality_id' => $this->municipality_id,
            'sector_id' => $this->sector_id,
            'role_id' => $this->role_id,
            'phone' => $this->phone,
            'status' => $this->status,
            'email_verified_at' => $this->email_verified_at,
            'two_factor_confirmed_at' => $this->two_factor_confirmed_at,
            'municipality' => new MunicipalityResource($this->whenLoaded('municipality')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
