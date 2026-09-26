<?php

namespace App\Modules\Business\Domain\Services;

use App\Models\Business;
use App\Models\Sector;
use App\Models\User;
use App\Modules\Business\Adapters\Repositories\BusinessRepository;

class UpdateBusinessService
{
    public function __construct(private readonly BusinessRepository $repository) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Business $business, array $data, ?int $municipalityId, ?int $userSectorId = null): Business
    {
        abort_unless(! $municipalityId || $business->municipality_id === $municipalityId, 404);
        $this->ensureUserCanUseSector($data['sector_id'] ?? null, $userSectorId);

        $data['municipality_id'] = $business->municipality_id;
        $this->ensureSectorBelongsToMunicipality($data['sector_id'] ?? null, $business->municipality_id);
        $this->ensureInspectorBelongsToMunicipality($data['inspector_id'] ?? null, $business->municipality_id);

        return $this->repository->update($business->id, $data);
    }

    private function ensureUserCanUseSector(mixed $sectorId, ?int $userSectorId): void
    {
        if (! $userSectorId) {
            return;
        }

        abort_unless((int) $sectorId === $userSectorId, 422);
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
