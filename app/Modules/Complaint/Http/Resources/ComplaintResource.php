<?php

namespace App\Modules\Complaint\Http\Resources;

use App\Models\Complaint;
use App\Modules\Municipality\Http\Resources\MunicipalityResource;
use App\Modules\Sector\Http\Resources\SectorResource;
use App\Modules\User\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'municipality_id' => $this->municipality_id,
            'sector_id' => $this->sector_id,
            'category' => $this->category,
            'category_label' => Complaint::categoryLabelFor($this->category),
            'description' => $this->description,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'address_text' => $this->address_text,
            'photo_url' => $this->photo_url,
            'citizen_name' => $this->citizen_name,
            'citizen_phone' => $this->citizen_phone,
            'tracking_code' => $this->tracking_code,
            'status' => $this->status,
            'status_label' => $this->status_label ?? Complaint::statusLabelFor($this->status),
            'assigned_user_id' => $this->assigned_user_id,
            'resolved_at' => $this->resolved_at,
            'municipality' => new MunicipalityResource($this->whenLoaded('municipality')),
            'sector' => new SectorResource($this->whenLoaded('sector')),
            'assigned_user' => new UserResource($this->whenLoaded('assignedUser')),
            'updates' => $this->whenLoaded('updates', fn () => $this->updates->map(
                fn ($update) => [
                    'id' => $update->id,
                    'previous_status' => $update->previous_status,
                    'new_status' => $update->new_status,
                    'status_label' => Complaint::statusLabelFor($update->new_status),
                    'note' => $update->note,
                    'user' => $update->relationLoaded('user') && $update->user
                        ? new UserResource($update->user)
                        : null,
                    'created_at' => $update->created_at,
                ]
            )),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
