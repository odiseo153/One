<?php

namespace App\Modules\Business\Domain\Services;

use App\Models\Business;
use App\Models\Sector;
use App\Models\User;
use App\Modules\Business\Adapters\Repositories\BusinessRepository;

class StoreBusinessService
{
    public function __construct(private readonly BusinessRepository $repository) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?int $municipalityId, ?int $userSectorId = null): Business
    {
        $this->ensureUserCanUseSector($data['sector_id'] ?? null, $userSectorId);
        $municipalityId = $this->resolveMunicipalityId(
            $data['sector_id'] ?? null,
            $data['municipality_id'] ?? $municipalityId,
        );

        $data['municipality_id'] = $municipalityId;
        $data['detected_at'] = $data['detected_at'] ?: now()->toDateString();

        $this->ensureSectorBelongsToMunicipality($data['sector_id'] ?? null, $municipalityId);
        $this->ensureInspectorBelongsToMunicipality($data['inspector_id'] ?? null, $municipalityId);

        return $this->repository->create($data);
    }

    private function ensureUserCanUseSector(mixed $sectorId, ?int $userSectorId): void
    {
        if (! $userSectorId) {
            return;
        }

        abort_unless((int) $sectorId === $userSectorId, 422);
    }

    private function resolveMunicipalityId(mixed $sectorId, ?int $municipalityId): int
    {
        if (! $sectorId) {
            abort_unless($municipalityId !== null, 422);

            return $municipalityId;
        }

        $sector = Sector::query()
            ->where('id', $sectorId)
            ->firstOrFail(['id', 'municipality_id']);

        if (! $municipalityId) {
            return $sector->municipality_id;
        }

        $hasOwnSectors = Sector::query()
            ->where('municipality_id', $municipalityId)
            ->whereNull('deleted_at')
            ->exists();

        return $hasOwnSectors ? $municipalityId : $sector->municipality_id;
    }

    private function ensureSectorBelongsToMunicipality(mixed $sectorId, int $municipalityId): void
    {
        if (! $sectorId) {
            return;
        }

        abort_unless(
            Sector::query()
                ->where('id', $sectorId)
                ->where('municipality_id', $municipalityId)
                ->exists(),
            422,
        );
    }

    private function ensureInspectorBelongsToMunicipality(mixed $inspectorId, int $municipalityId): void
    {
        if (! $inspectorId) {
            return;
        }

        abort_unless(
            User::query()
                ->where('id', $inspectorId)
                ->where('municipality_id', $municipalityId)
                ->exists(),
            422,
        );
    }
}
