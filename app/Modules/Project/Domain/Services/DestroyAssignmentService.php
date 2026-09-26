<?php

namespace App\Modules\Project\Domain\Services;

use App\Modules\Project\Adapters\Repositories\ProjectRepository;

class DestroyAssignmentService
{
    public function __construct(
        private readonly ProjectRepository $repository,
    ) {}

    public function execute(int $projectId, int $projectUserId): void
    {
        $this->repository->destroyAssignment($projectId, $projectUserId);
    }
}
