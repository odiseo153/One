<?php

namespace App\Modules\Business\Domain\Services;

use App\Models\Business;
use App\Modules\Business\Adapters\Repositories\BusinessRepository;
use App\Modules\Sector\Adapters\Repositories\SectorRepository;
use App\Modules\User\Adapters\Repositories\UserRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateBusinessService
{
    public function __construct(
        private readonly BusinessRepository $repository,
        private readonly SectorRepository $sectorRepository,
        private readonly UserRepository $userRepository,
    ) {}

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

        $employees = $data['employees'] ?? [];
        $photo = $data['photo'] ?? null;
        unset($data['employees'], $data['photo'], $data['is_registered']);

        $previousPhotoUrl = $business->photo_url;

        if ($photo instanceof UploadedFile) {
            $path = $photo->store('businesses', 'public');
            $data['photo_url'] = $path ? Storage::url($path) : null;
        }

        $updated = $this->repository->updateWithEmployees($business->id, $data, $employees);

        if ($photo instanceof UploadedFile && $previousPhotoUrl) {
            Storage::disk('public')->delete(Str::after($previousPhotoUrl, '/storage/'));
        }

        return $updated;
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
