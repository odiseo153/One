<?php

namespace App\Modules\Project\Domain\Services;

use App\Models\Project;
use App\Modules\Project\Adapters\Repositories\ProjectRepository;

class UpdateProjectStatusService
{
    private const TRANSITIONS = [
        Project::STATUS_PLANNED => [Project::STATUS_IN_PROGRESS, Project::STATUS_CANCELLED],
        Project::STATUS_IN_PROGRESS => [Project::STATUS_PAUSED, Project::STATUS_COMPLETED, Project::STATUS_CANCELLED],
        Project::STATUS_PAUSED => [Project::STATUS_IN_PROGRESS, Project::STATUS_CANCELLED],
        Project::STATUS_COMPLETED => [Project::STATUS_IN_PROGRESS],
        Project::STATUS_CANCELLED => [],
    ];

    public function __construct(
        private readonly ProjectRepository $repository,
    ) {}

    public function execute(int $projectId, array $data, ?int $userId): Project
    {
        $project = $this->repository->findById($projectId);

        $allowedTransitions = self::TRANSITIONS[$project->status] ?? [];

        if (! in_array($data['status'], $allowedTransitions)) {
            abort(422, __('El estado seleccionado no es válido para esta obra.'));
        }

        return $this->repository->changeStatus(
            $project,
            $data['status'],
            $userId,
            $data['note'] ?? null,
        );
    }

    public static function getAllowedTransitions(string $currentStatus): array
    {
        return self::TRANSITIONS[$currentStatus] ?? [];
    }
}
