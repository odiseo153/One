<?php

namespace App\Modules\Sector\Domain\Services;

use App\Models\Sector;
use App\Modules\Sector\Adapters\Repositories\SectorRepository;

class ManageSectorsService
{
    public function __construct(private readonly SectorRepository $repository) {}

    public function index(?string $search, string $status): array
    {
        return $this->repository->getManagementData($search, $status);
    }

    public function create(array $data): Sector
    {
        return $this->repository->create($data);
    }

    public function update(Sector $sector, array $data): void
    {
        $this->repository->update($sector->id, $data);
    }

    public function delete(Sector $sector): void
    {
        $this->repository->delete($sector->id);
    }
}
