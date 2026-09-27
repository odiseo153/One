<?php

namespace App\Modules\Municipality\Domain\Services;

use App\Models\Municipality;
use App\Modules\Municipality\Adapters\Repositories\MunicipalityRepository;

class ManageMunicipalitiesService
{
    public function __construct(private readonly MunicipalityRepository $repository) {}

    public function index(?string $search, string $status): array
    {
        return $this->repository->getManagementData($search, $status);
    }

    public function create(array $data): Municipality
    {
        return $this->repository->create($data);
    }

    public function update(Municipality $municipality, array $data): void
    {
        $this->repository->update($municipality->id, $data);
    }

    public function delete(Municipality $municipality): void
    {
        $this->repository->delete($municipality->id);
    }
}
