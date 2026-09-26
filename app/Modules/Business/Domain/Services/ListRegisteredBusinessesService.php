<?php

namespace App\Modules\Business\Domain\Services;

use App\Models\Business;
use App\Modules\Business\Adapters\Repositories\BusinessRepository;
use DateTimeInterface;

class ListRegisteredBusinessesService
{
    public function __construct(
        private readonly BusinessRepository $businessRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function execute(int $perPage = 15, array $filters = []): array
    {
        $businesses = $this->businessRepository->getForTable($perPage);

        $businesses->getCollection()->transform(
            fn (Business $business): array => [
                'id' => $business->id,
                'name' => $business->name,
                'category' => $business->category,
                'rnc' => $business->rnc,
                'address_text' => $business->address_text,
                'registration_status' => $business->registration_status,
                'detected_at' => $this->dateValue($business->detected_at),
                'last_verified_at' => $this->dateValue($business->last_verified_at),
                'sector' => $business->sector
                    ? ['id' => $business->sector->id, 'name' => $business->sector->name]
                    : null,
                'municipality' => $business->municipality
                    ? [
                        'id' => $business->municipality->id,
                        'name' => $business->municipality->name,
                        'province' => $business->municipality->province
                            ? [
                                'id' => $business->municipality->province->id,
                                'name' => $business->municipality->province->name,
                            ]
                            : null,
                    ]
                    : null,
                'inspector' => $business->inspector
                    ? ['id' => $business->inspector->id, 'name' => $business->inspector->name]
                    : null,
            ],
        );

        return [
            'businesses' => $businesses,
            'filters' => [
                'filter' => $filters['filter'] ?? [],
                'sort' => is_string($filters['sort'] ?? null) ? $filters['sort'] : null,
            ],
        ];
    }

    private function dateValue(DateTimeInterface|string|null $value): ?string
    {
        return $value instanceof DateTimeInterface ? $value->format('Y-m-d') : $value;
    }
}
