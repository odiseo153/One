<?php

namespace App\Modules\Business\Domain\Services;

use App\Models\Business;
use App\Models\BusinessEmployee;
use App\Models\Sector;
use BackedEnum;
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
            'is_registered' => $business->registration_status === BusinessStatusService::REGISTERED,
            'property_status' => $this->enumValue($business->property_status),
            'document_type' => $this->enumValue($business->document_type),
            'document_number' => $business->document_number,
            'rnc' => $business->rnc,
            'primary_ciiu_id' => $business->primary_ciiu_id,
            'primary_activity' => $business->primary_activity,
            'secondary_ciiu_id' => $business->secondary_ciiu_id,
            'secondary_activity' => $business->secondary_activity,
            'photo_url' => $business->photo_url,
            'employees' => $business->employees->map(fn (BusinessEmployee $employee): array => [
                'id' => $employee->id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'document_type' => $this->enumValue($employee->document_type),
                'document_number' => $employee->document_number,
                'salary' => (string) $employee->salary,
            ])->values(),
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

    private function enumValue(mixed $value): ?int
    {
        if ($value instanceof BackedEnum) {
            return (int) $value->value;
        }

        return is_int($value) ? $value : null;
    }
}
