<?php

namespace App\Modules\Project\Domain\Services;

use App\Models\ProjectMilestone;
use App\Modules\Project\Adapters\Repositories\ProjectRepository;

class UpdateMilestoneStatusService
{
    public function __construct(
        private readonly ProjectRepository $repository,
    ) {}

    public function execute(int $projectId, int $milestoneId, string $status): void
    {
        $project = $this->repository->findById($projectId);

        $milestone = ProjectMilestone::query()
            ->where('project_id', $project->id)
            ->findOrFail($milestoneId);

        if ($status === ProjectMilestone::STATUS_COMPLETED) {
            $milestone->markCompleted();
        } else {
            $milestone->update([
                'status' => $status,
                'completed_date' => null,
            ]);
        }
    }
}
