<?php

namespace App\Modules\Project\Adapters\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\Municipality;
use App\Models\Project as ProjectModel;
use App\Models\ProjectUser;
use App\Models\Sector;
use App\Models\User;
use App\Modules\Project\Domain\Contracts\ProjectRepositoryPort;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\AllowedSort;

class ProjectRepository extends BaseRepository implements ProjectRepositoryPort
{
    public function __construct()
    {
        parent::__construct(new ProjectModel);
    }

    protected function setupDefaults(): void
    {
        $this->allowedFilters = [
            AllowedFilter::exact('id'),
            AllowedFilter::partial('name'),
            AllowedFilter::partial('contractor_name'),
            AllowedFilter::partial('address_text'),
            AllowedFilter::exact('status'),
            AllowedFilter::exact('type'),
            AllowedFilter::exact('sector_id'),
            AllowedFilter::exact('municipality_id'),
            AllowedFilter::exact('created_by'),
            AllowedFilter::callback('from', fn ($query, $value) => $query->whereDate('start_date_planned', '>=', $value)),
            AllowedFilter::callback('to', fn ($query, $value) => $query->whereDate('end_date_planned', '<=', $value)),
        ];

        $this->allowedSorts = [
            AllowedSort::field('id'),
            AllowedSort::field('name'),
            AllowedSort::field('status'),
            AllowedSort::field('type'),
            AllowedSort::field('progress_percentage'),
            AllowedSort::field('budget_assigned'),
            AllowedSort::field('budget_executed'),
            AllowedSort::field('start_date_planned'),
            AllowedSort::field('end_date_planned'),
            AllowedSort::field('created_at'),
            AllowedSort::field('updated_at'),
        ];

        $this->allowedIncludes = [
            AllowedInclude::relationship('municipality'),
            AllowedInclude::relationship('sector'),
            AllowedInclude::relationship('creator'),
            AllowedInclude::relationship('projectUsers.user'),
            AllowedInclude::relationship('updates.user'),
            AllowedInclude::relationship('updates.photos'),
            AllowedInclude::relationship('photos.uploader'),
            AllowedInclude::relationship('milestones'),
        ];

        $this->allowedCounts = ['updates'];

        $this->defaultSort = '-id';
    }

    public function getAll(int $perPage, ?string $defaultSort = null, array $with = []): LengthAwarePaginator
    {
        return parent::getAll($perPage, $defaultSort, $with);
    }

    public function create(array $data): ProjectModel
    {
        return ProjectModel::create($data)->load($this->includeNames());
    }

    public function findById($id): ProjectModel
    {
        return ProjectModel::with($this->includeNames())->findOrFail($id);
    }

    public function getStats(?int $municipalityId): array
    {
        $query = ProjectModel::query()
            ->when($municipalityId, fn ($q) => $q->where('municipality_id', $municipalityId));

        $projects = $query->select(['status', 'budget_assigned', 'budget_executed'])->get();

        $byStatus = collect(ProjectModel::STATUSES)
            ->mapWithKeys(fn (string $status) => [$status => 0])
            ->all();

        foreach ($projects as $project) {
            $byStatus[$project->status] = ($byStatus[$project->status] ?? 0) + 1;
        }

        return [
            'total' => $projects->count(),
            'by_status' => $byStatus,
            'assigned' => round((float) $projects->sum('budget_assigned'), 2),
            'executed' => round((float) $projects->sum('budget_executed'), 2),
        ];
    }

    public function getForMap(int $municipalityId): array
    {
        $sectors = Sector::query()
            ->where('municipality_id', $municipalityId)
            ->get(['id', 'name', 'geojson_polygon'])
            ->toArray();

        $municipality = Municipality::query()
            ->find($municipalityId, ['id', 'name', 'province_id', 'geojson_polygon']);

        return [
            'municipality' => $municipality?->toArray() ?: null,
            'municipalities' => $municipality ? [$municipality->toArray()] : [],
            'sectors' => $sectors,
        ];
    }

    public function storeAssignment(int $projectId, int $userId, string $role): void
    {
        ProjectUser::updateOrCreate(
            ['project_id' => $projectId, 'user_id' => $userId],
            ['role_in_project' => $role, 'assigned_at' => now()],
        );
    }

    public function destroyAssignment(int $projectId, int $projectUserId): void
    {
        ProjectUser::query()
            ->where('project_id', $projectId)
            ->findOrFail($projectUserId)
            ->delete();
    }

    public function getSectorOptions(?int $municipalityId): array
    {
        return Sector::query()
            ->whereNull('deleted_at')
            ->when($municipalityId, fn ($q) => $q->where('municipality_id', $municipalityId))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    public function getAllSectorOptions(): array
    {
        return Sector::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'municipality_id', 'name'])
            ->toArray();
    }

    public function getMunicipalityOptions(?int $userMunicipalityId): array
    {
        if ($userMunicipalityId) {
            return Municipality::query()
                ->where('id', $userMunicipalityId)
                ->get(['id', 'name'])
                ->toArray();
        }

        return Municipality::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    public function getMunicipality(?int $municipalityId): ?array
    {
        if (! $municipalityId) {
            return null;
        }

        return Municipality::query()
            ->find($municipalityId, ['id', 'name'])?->toArray();
    }

    public function getUserOptions(?int $municipalityId): array
    {
        return User::query()
            ->whereNull('deleted_at')
            ->when($municipalityId, fn ($q) => $q->where('municipality_id', $municipalityId))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    public function getTypeOptions(): array
    {
        return collect(ProjectModel::TYPES)
            ->map(fn (string $type) => ['value' => $type, 'label' => ProjectModel::typeLabelFor($type)])
            ->all();
    }

    public function getStatusOptions(): array
    {
        return collect(ProjectModel::STATUSES)
            ->map(fn (string $status) => ['value' => $status, 'label' => ProjectModel::statusLabelFor($status)])
            ->all();
    }

    public function getRoleOptions(): array
    {
        return collect(ProjectUser::ROLES)
            ->map(fn (string $role) => ['value' => $role, 'label' => ProjectUser::roleLabelFor($role)])
            ->all();
    }
}
