<?php

namespace App\Modules\Project\Domain\Services;

use App\Models\Project;
use App\Modules\Project\Adapters\Repositories\ProjectRepository;

class ListProjectMarkersService
{
    public function __construct(private readonly ProjectRepository $repository) {}

    public function execute(?int $userMunicipalityId, array $filters): array
    {
        $municipalityFilter = isset($filters['municipality_id']) ? (int) $filters['municipality_id'] : 0;
        $provinceFilter = isset($filters['province_id']) ? (int) $filters['province_id'] : 0;
        $municipalityId = $municipalityFilter ?: $userMunicipalityId;

        return $this->repository
            ->getMarkers($municipalityId, $provinceFilter ?: null)
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'name' => $project->name,
                'type' => $project->type,
                'status' => $project->status,
                'progress_percentage' => $project->progress_percentage,
                'latitude' => $project->latitude,
                'longitude' => $project->longitude,
                'sector_id' => $project->sector_id,
                'municipality_id' => $project->municipality_id,
                'sector' => $project->sector ? ['id' => $project->sector->id, 'name' => $project->sector->name] : null,
                'municipality' => $project->municipality ? ['id' => $project->municipality->id, 'name' => $project->municipality->name] : null,
            ])
            ->all();
    }
}
