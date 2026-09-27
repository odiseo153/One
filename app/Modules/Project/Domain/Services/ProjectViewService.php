<?php

namespace App\Modules\Project\Domain\Services;

use App\Models\Project;
use App\Modules\Project\Adapters\Repositories\ProjectRepository;

class ProjectViewService
{
    public function __construct(private readonly ProjectRepository $repository) {}

    public function sectorOptions(?int $municipalityId): array
    {
        return $this->repository->getSectorOptions($municipalityId);
    }

    public function allSectorOptions(): array
    {
        return $this->repository->getAllSectorOptions();
    }

    public function municipalityOptions(?int $municipalityId): array
    {
        return $this->repository->getMunicipalityOptions($municipalityId);
    }

    public function municipality(?int $municipalityId): ?array
    {
        return $this->repository->getMunicipality($municipalityId);
    }

    public function userOptions(?int $municipalityId): array
    {
        return $this->repository->getUserOptions($municipalityId);
    }

    public function typeOptions(): array
    {
        return $this->repository->getTypeOptions();
    }

    public function statusOptions(): array
    {
        return $this->repository->getStatusOptions();
    }

    public function roleOptions(): array
    {
        return $this->repository->getRoleOptions();
    }

    public function stats(?int $municipalityId): array
    {
        return $this->repository->getStats($municipalityId);
    }

    public function map(int $municipalityId): array
    {
        return $this->repository->getForMap($municipalityId);
    }

    public function loadUpdateFormRelations(Project $project): Project
    {
        return $this->repository->loadUpdateFormRelations($project);
    }
}
