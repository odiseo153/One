<?php

namespace App\Modules\Municipality\Http\Resources;

use App\Modules\Province\Http\Resources\ProvinceResource;
use App\Modules\Sector\Http\Resources\SectorResource;
use App\Modules\User\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class MunicipalityResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo_url' => $this->logo_url,
            'domain' => $this->domain,
            'subdomain' => $this->subdomain,
            'province_id' => $this->province_id,
            'geojson_polygon' => $this->geojson_polygon,
            'status' => $this->status,
            'registration_date' => $this->registration_date,
            'contracted_plan' => $this->contracted_plan,
            'province' => new ProvinceResource($this->whenLoaded('province')),
            'users' => UserResource::collection($this->whenLoaded('users')),
            'sectors' => SectorResource::collection($this->whenLoaded('sectors')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
