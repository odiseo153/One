<?php

namespace App\Modules\Business\Domain\Services;

use App\Models\Business;
use App\Modules\Business\Adapters\Repositories\BusinessRepository;
use App\Modules\Sector\Adapters\Repositories\SectorRepository;
use App\Modules\User\Adapters\Repositories\UserRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StoreBusinessService
{
    public function __construct(
        private readonly BusinessRepository $repository,
        private readonly SectorRepository $sectorRepository,
        private readonly UserRepository $userRepository,
    ) {}

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
        $data['detected_at'] = ($data['detected_at'] ?? null) ?: now()->toDateString();

        $this->ensureSectorBelongsToMunicipality($data['sector_id'] ?? null, $municipalityId);
        $this->ensureInspectorBelongsToMunicipality($data['inspector_id'] ?? null, $municipalityId);

        $employees = $data['employees'] ?? [];
        $photo = $data['photo'] ?? null;
        unset($data['employees'], $data['photo'], $data['is_registered']);

        if ($photo instanceof UploadedFile) {
            $path = $photo->store('businesses', 'public');
            $data['photo_url'] = $path ? Storage::url($path) : null;
        }

        return $this->repository->createWithEmployees($data, $employees);
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

        $sectorMunicipalityId = $this->sectorRepository->municipalityId((int) $sectorId);
        abort_unless($sectorMunicipalityId !== null, 404);

        if (! $municipalityId) {
            return $sectorMunicipalityId;
        }

        $hasOwnSectors = $this->sectorRepository->municipalityHasSectors($municipalityId);

        return $hasOwnSectors ? $municipalityId : $sectorMunicipalityId;
    }

    private function ensureSectorBelongsToMunicipality(mixed $sectorId, int $municipalityId): void
    {
        if (! $sectorId) {
            return;
        }

        abort_unless(
            $this->sectorRepository->belongsToMunicipality((int) $sectorId, $municipalityId),
            422,
        );
    }

    private function ensureInspectorBelongsToMunicipality(mixed $inspectorId, int $municipalityId): void
    {
        if (! $inspectorId) {
            return;
        }

        abort_unless(
            $this->userRepository->inspectorBelongsToMunicipality((int) $inspectorId, $municipalityId),
            422,
        );
    }
}
