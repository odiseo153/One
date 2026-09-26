<?php

namespace App\Modules\Project\Domain\Services;

use App\Models\ProjectMilestone;
use App\Modules\Project\Adapters\Repositories\ProjectRepository;

class StoreMilestoneService
{
    public function __construct(
        private readonly ProjectRepository $repository,
    ) {}

    public function execute(int $projectId, array $data): void
    {
        $project = $this->repository->findById($projectId);

        ProjectMilestone::create([
            'project_id' => $project->id,
            'name' => $data['name'],
            'order' => $project->milestones()->count() + 1,
            'planned_date' => $data['planned_date'] ?? null,
            'status' => ProjectMilestone::STATUS_PENDING,
        ]);
    }
}
