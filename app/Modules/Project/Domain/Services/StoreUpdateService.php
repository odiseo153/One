<?php

namespace App\Modules\Project\Domain\Services;

use App\Models\Project;
use App\Modules\Project\Adapters\Repositories\ProjectRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StoreUpdateService
{
    public function __construct(
        private readonly ProjectRepository $repository,
    ) {}

    public function execute(int $projectId, array $data, ?int $userId): void
    {
        $project = $this->repository->findById($projectId);

        $photos = array_values(array_filter($data['photos'] ?? []));
        $captions = array_values(array_filter($data['captions'] ?? []));

        $newStatus = $data['status'] ?? null;
        $progress = (int) $data['progress_percentage'];

        if ($progress >= 100 && $newStatus === null) {
            $newStatus = Project::STATUS_COMPLETED;
        }

        $project->progress_percentage = $newStatus === Project::STATUS_COMPLETED ? 100 : $progress;
        $project->budget_executed = $this->applyBudget($project, (float) ($data['budget_spent'] ?? 0));

        if ($newStatus !== null && $newStatus !== $project->status) {
            if ($newStatus === Project::STATUS_COMPLETED) {
                $project->end_date_real = $project->end_date_real ?? $data['update_date'];
                $project->status = Project::STATUS_COMPLETED;
            } else {
                if ($newStatus === Project::STATUS_IN_PROGRESS && $project->start_date_real === null) {
                    $project->start_date_real = $data['update_date'];
                }
                $project->status = $newStatus;
            }
        }

        $this->repository->saveProject($project);

        $update = $this->repository->createUpdate([
            'project_id' => $project->id,
            'user_id' => $userId,
            'update_date' => $data['update_date'],
            'progress_percentage_at_update' => $project->progress_percentage,
            'description' => $data['description'] ?? null,
            'status_at_update' => $project->status,
            'budget_spent_at_update' => $data['budget_spent'] ?? null,
        ]);

        foreach ($photos as $index => $photo) {
            if (! $photo instanceof UploadedFile) {
                continue;
            }

            $path = $photo->store('projects', 'public');

            if (! $path) {
                continue;
            }

            $this->repository->createPhoto([
                'project_id' => $project->id,
                'project_update_id' => $update->id,
                'photo_url' => Storage::url($path),
                'caption' => $captions[$index] ?? null,
                'taken_at' => $data['taken_at'] ?? $data['update_date'],
                'uploaded_by' => $userId,
            ]);
        }
    }

    private function applyBudget(Project $project, float $spent): float
    {
        $executed = (float) $project->budget_executed + $spent;
        $assigned = (float) $project->budget_assigned;

        return $assigned > 0 ? round(min($executed, $assigned), 2) : round($executed, 2);
    }
}
