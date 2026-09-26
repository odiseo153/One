<?php

namespace App\Modules\Business\Domain\Services;

use App\Models\Business;
use App\Models\Sector;
use DateTimeInterface;

class BusinessPayloadService
{
    /**
     * @return array<string, mixed>
     */
    public function execute(Business $business): array
    {
        $sector = $business->sector;

        return [
            'id' => $business->id,
            'municipality_id' => $business->municipality_id,
            'sector_id' => $business->sector_id,
            'sector' => $sector instanceof Sector ? [
                'id' => $sector->id,
                'name' => $sector->name,
            ] : null,
            'name' => $business->name,
            'category' => $business->category,
            'latitude' => (float) $business->latitude,
            'longitude' => (float) $business->longitude,
            'address_text' => $business->address_text,
            'registration_status' => $business->registration_status,
            'rnc' => $business->rnc,
            'detected_at' => $this->datePayload($business->detected_at),
            'last_verified_at' => $this->datePayload($business->last_verified_at),
            'inspector_id' => $business->inspector_id,
        ];
    }

    private function datePayload(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value) && $value !== '') {
            return substr($value, 0, 10);
        }

        return null;
    }
}
