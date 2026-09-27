<?php

namespace App\Modules\Municipality\Domain\Services;

use App\Modules\Municipality\Adapters\Repositories\MunicipalityRepository;

class MunicipalityOptionsService
{
    public function __construct(private readonly MunicipalityRepository $repository) {}

    public function options(?int $municipalityId = null): array
    {
        return $this->repository->options($municipalityId);
    }

    public function find(?int $municipalityId): ?array
    {
        return $this->repository->findOption($municipalityId);
    }
}
