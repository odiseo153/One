<?php

namespace App\Modules\Project\Domain\Services;

use App\Modules\Project\Adapters\Repositories\ProjectRepository;

class DestroyMilestoneService
{
    public function __construct(
        private readonly ProjectRepository $repository,
    ) {}

    public function execute(int $projectId, int $milestoneId): void
    {
        $project = $this->repository->findById($projectId);

        $this->repository->deleteMilestone($project->id, $milestoneId);
    }
}
