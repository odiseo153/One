<?php

namespace App\Modules\Sector\Http\Resources;

use App\Modules\Municipality\Http\Resources\MunicipalityResource;
use Illuminate\Http\Resources\Json\JsonResource;

class SectorResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'municipality_id' => $this->municipality_id,
            'name' => $this->name,
            'geojson_polygon' => $this->geojson_polygon,
            'municipality' => new MunicipalityResource($this->whenLoaded('municipality')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
