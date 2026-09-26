<?php

namespace App\Modules\Province\Http\Resources;

use App\Modules\Municipality\Http\Resources\MunicipalityResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProvinceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'geojson_polygon' => $this->geojson_polygon,
            'municipalities' => MunicipalityResource::collection($this->whenLoaded('municipalities')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
