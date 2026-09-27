<?php

namespace App\Modules\Project\Domain\Services;

use App\Modules\Project\Adapters\Repositories\ProjectRepository;

class UpdateMilestoneStatusService
{
    public function __construct(
        private readonly ProjectRepository $repository,
    ) {}

    public function execute(int $projectId, int $milestoneId, string $status): void
    {
        $project = $this->repository->findById($projectId);

        $this->repository->updateMilestoneStatus($project->id, $milestoneId, $status);
    }
}
