<?php

namespace App\Modules\Business\Domain\Services;

use App\Models\Business;
use App\Modules\Business\Adapters\Repositories\BusinessRepository;
use App\Modules\Business\Adapters\Repositories\SerpApiMapsRepository;
use App\Modules\Sector\Adapters\Repositories\SectorRepository;
use Illuminate\Support\Collection;

class DiscoverMapPlacesService
{
    public function __construct(
        private readonly SerpApiMapsRepository $serpApiMapsRepository,
        private readonly BusinessRepository $businessRepository,
        private readonly SectorRepository $sectorRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function execute(array $data, ?int $userMunicipalityId, ?int $userSectorId): array
    {
        $latitude = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];
        $query = trim((string) ($data['query'] ?? 'negocios supermercados colmados'));
        $sectorId = $this->integerValue($data['sector_id'] ?? null);
        $municipalityId = $this->resolveMunicipalityId(
            $sectorId,
            $this->integerValue($data['municipality_id'] ?? null) ?: $userMunicipalityId,
        );

        if ($userSectorId) {
            $sectorId = $userSectorId;
            $municipalityId = $this->sectorRepository->municipalityId($userSectorId);
        }

        $payload = $this->serpApiMapsRepository->search($latitude, $longitude, $query);
        $businesses = $this->businessesForMatching($municipalityId, $sectorId);
        $localResults = $payload['local_results'] ?? [];

        if (! is_array($localResults)) {
            $localResults = [];
        }

        return [
            'places' => collect($localResults)
                ->filter(fn (mixed $result): bool => is_array($result))
                ->map(fn (array $result): ?array => $this->placePayload($result, $businesses, $municipalityId, $sectorId))
                ->filter()
                ->values(),
        ];
    }

    private function resolveMunicipalityId(?int $sectorId, ?int $municipalityId): ?int
    {
        if (! $sectorId) {
            return $municipalityId;
        }

        return $this->sectorRepository->municipalityId($sectorId) ?: $municipalityId;
    }

    /**
     * @return Collection<int, Business>
     */
    private function businessesForMatching(?int $municipalityId, ?int $sectorId): Collection
    {
        return $this->businessRepository->getForMatching($municipalityId, $sectorId);
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  Collection<int, Business>  $businesses
     * @return array<string, mixed>|null
     */
    private function placePayload(array $result, Collection $businesses, ?int $municipalityId, ?int $sectorId): ?array
    {
        $coordinates = $result['gps_coordinates'] ?? null;

        if (! is_array($coordinates) || ! isset($coordinates['latitude'], $coordinates['longitude'])) {
            return null;
        }

        $latitude = (float) $coordinates['latitude'];
        $longitude = (float) $coordinates['longitude'];
        $matchedBusiness = $this->matchedBusiness($businesses, (string) ($result['title'] ?? ''), $latitude, $longitude);

        return [
            'id' => (string) ($result['data_id'] ?? $result['data_cid'] ?? md5(($result['title'] ?? '').$latitude.$longitude)),
            'title' => (string) ($result['title'] ?? 'Sin nombre'),
            'category' => $this->category($result),
            'address' => $result['address'] ?? null,
            'phone' => $result['phone'] ?? null,
            'rating' => $result['rating'] ?? null,
            'reviews' => $result['reviews'] ?? null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'municipality_id' => $matchedBusiness ? $matchedBusiness->municipality_id : $municipalityId,
            'sector_id' => $matchedBusiness ? $matchedBusiness->sector_id : $sectorId,
            'registered' => $matchedBusiness !== null && $matchedBusiness->registration_status === BusinessStatusService::REGISTERED,
            'business_id' => $matchedBusiness?->id,
            'registration_status' => $matchedBusiness?->registration_status,
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function category(array $result): ?string
    {
        if (isset($result['type']) && is_string($result['type'])) {
            return $result['type'];
        }

        if (isset($result['types']) && is_array($result['types'])) {
            return implode(', ', array_filter($result['types'], 'is_string'));
        }

        return null;
    }

    /**
     * @param  Collection<int, Business>  $businesses
     */
    private function matchedBusiness(Collection $businesses, string $title, float $latitude, float $longitude): ?Business
    {
        $normalizedTitle = $this->normalize($title);

        return $businesses->first(function (Business $business) use ($latitude, $longitude, $normalizedTitle): bool {
            $distance = $this->distanceMeters($latitude, $longitude, (float) $business->latitude, (float) $business->longitude);

            if ($distance <= 35) {
                return true;
            }

            return $normalizedTitle !== '' && $this->normalize($business->name) === $normalizedTitle;
        });
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');

        return preg_replace('/[^\pL\pN]+/u', '', $value) ?? '';
    }

    private function distanceMeters(float $latA, float $lngA, float $latB, float $lngB): float
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($latB - $latA);
        $lngDelta = deg2rad($lngB - $lngA);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($latA)) * cos(deg2rad($latB)) * sin($lngDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function integerValue(mixed $value): ?int
    {
        if (! is_numeric($value) || (int) $value <= 0) {
            return null;
        }

        return (int) $value;
    }
}
