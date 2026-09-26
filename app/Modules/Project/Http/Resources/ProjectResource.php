<?php

namespace App\Modules\Project\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'municipality_id' => $this->municipality_id,
            'sector_id' => $this->sector_id,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'status' => $this->status,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'address_text' => $this->address_text,
            'budget_assigned' => $this->budget_assigned,
            'budget_executed' => $this->budget_executed,
            'progress_percentage' => $this->progress_percentage,
            'start_date_planned' => $this->start_date_planned,
            'end_date_planned' => $this->end_date_planned,
            'start_date_real' => $this->start_date_real,
            'end_date_real' => $this->end_date_real,
            'contractor_name' => $this->contractor_name,
            'municipality' => $this->whenLoaded('municipality'),
            'sector' => $this->whenLoaded('sector'),
            'creator' => $this->whenLoaded('creator'),
            'updates_count' => $this->whenCounted('updates'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
