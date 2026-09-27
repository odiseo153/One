<?php

namespace App\Modules\Business\Domain\Services;

use App\Models\Business;
use App\Modules\Business\Adapters\Repositories\BusinessRepository;
use App\Modules\Municipality\Adapters\Repositories\MunicipalityRepository;
use App\Modules\Province\Adapters\Repositories\ProvinceRepository;
use App\Modules\Sector\Adapters\Repositories\SectorRepository;
use App\Modules\User\Adapters\Repositories\UserRepository;

class ListBusinessMapService
{
    public function __construct(
        private readonly BusinessRepository $businessRepository,
        private readonly SectorRepository $sectorRepository,
        private readonly UserRepository $userRepository,
        private readonly ProvinceRepository $provinceRepository,
        private readonly MunicipalityRepository $municipalityRepository,
        private readonly BusinessPayloadService $payloadService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function execute(?int $municipalityId, array $filters = [], ?int $userSectorId = null): array
    {
        $provinceFilter = $this->integerFilter($filters['province_id'] ?? null);
        $municipalityFilter = $this->integerFilter($filters['municipality_id'] ?? null);
        $effectiveMunicipalityId = $municipalityFilter ?: $municipalityId;
        $businesses = $this->businessRepository->getForMap($effectiveMunicipalityId, $provinceFilter);

        return [
            'municipality' => $this->municipalityRepository->findOption($effectiveMunicipalityId),
            'provinces' => $this->provinceRepository->getActiveOptions(),
            'municipalities' => $this->municipalityRepository->getActiveOptions($provinceFilter),
            'sectors' => $this->sectorRepository->getForMap(
                $effectiveMunicipalityId,
                ! $provinceFilter && ! $municipalityFilter,
                $provinceFilter,
            ),
            'businesses' => $businesses
                ->map(fn (Business $business): array => $this->payloadService->execute($business))
                ->values(),
            'inspectors' => $this->userRepository->getForMap($effectiveMunicipalityId, true),
            'userSectorId' => $userSectorId,
            'statusOptions' => BusinessStatusService::STATUSES,
            'summary' => $this->businessRepository->summary($businesses),
            'filters' => [
                'province_id' => $provinceFilter ? (string) $provinceFilter : null,
                'municipality_id' => $municipalityFilter ? (string) $municipalityFilter : null,
            ],
        ];
    }

    private function integerFilter(mixed $value): ?int
    {
        if (! is_numeric($value) || (int) $value <= 0) {
            return null;
        }

        return (int) $value;
    }
}
