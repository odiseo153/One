<?php

namespace App\Modules\Project\Domain\Services;

use App\Modules\Project\Adapters\Repositories\ProjectRepository;

class StoreAssignmentService
{
    public function __construct(
        private readonly ProjectRepository $repository,
    ) {}

    public function execute(int $projectId, int $userId, string $role): void
    {
        $this->repository->storeAssignment($projectId, $userId, $role);
    }
}
