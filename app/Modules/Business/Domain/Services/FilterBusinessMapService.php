<?php

namespace App\Modules\Business\Domain\Services;

use App\Models\Business;
use App\Modules\Business\Adapters\Repositories\BusinessRepository;

class FilterBusinessMapService
{
    public function __construct(
        private readonly BusinessRepository $businessRepository,
        private readonly BusinessGeometryService $geometryService,
        private readonly BusinessPayloadService $payloadService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function execute(?int $municipalityId, array $filters): array
    {
        $businesses = $this->businessRepository->getForMap($municipalityId);
        $businesses = $this->geometryService->filterInsidePolygon(
            $businesses,
            $filters['polygon'] ?? null,
        );

        return [
            'data' => $businesses
                ->map(fn (Business $business): array => $this->payloadService->execute($business))
                ->values(),
            'summary' => $this->businessRepository->summary($businesses),
        ];
    }
}
