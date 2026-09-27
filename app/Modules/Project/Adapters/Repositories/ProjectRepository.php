<?php

namespace App\Modules\Project\Adapters\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Core\Support\EntityDateHelper;
use App\Core\Support\EntitySearchHelper;
use App\Models\Municipality;
use App\Models\Project as ProjectModel;
use App\Models\ProjectMilestone;
use App\Models\ProjectPhoto;
use App\Models\ProjectUpdate;
use App\Models\ProjectUser;
use App\Models\Sector;
use App\Models\User;
use App\Modules\Project\Domain\Contracts\ProjectRepositoryPort;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/** @extends BaseRepository<ProjectModel> */
class ProjectRepository extends BaseRepository implements ProjectRepositoryPort
{
    protected string $defaultSort = '-id';

    public function __construct()
    {
        parent::__construct(ProjectModel::class);
    }

    protected function getFilters(): array
    {
        return [
            AllowedFilter::exact('id'),
            AllowedFilter::partial('name'),
            AllowedFilter::callback('search', fn ($query, $value) => EntitySearchHelper::apply(
                $query,
                $value,
                [
                    'columns' => ['name', 'contractor_name', 'address_text'],
                    'relations' => ['sector' => ['name'], 'municipality' => ['name']],
                ],
            )),
            AllowedFilter::partial('contractor_name'),
            AllowedFilter::partial('address_text'),
            AllowedFilter::exact('status'),
            AllowedFilter::exact('type'),
            AllowedFilter::exact('sector_id'),
            AllowedFilter::exact('municipality_id'),
            AllowedFilter::callback('province_id', fn ($query, $value) => $query->whereHas(
                'municipality',
                fn ($municipality) => $municipality->where('province_id', $value),
            )),
            AllowedFilter::exact('created_by'),
            AllowedFilter::callback('from', fn ($query, $value) => EntityDateHelper::applyFrom(
                $query,
                $value,
                'start_date_planned',
                false,
            )),
            AllowedFilter::callback('to', fn ($query, $value) => EntityDateHelper::applyTo(
                $query,
                $value,
                'end_date_planned',
                false,
            )),
        ];
    }

    protected function getSorts(): array
    {
        return [
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
    }

    protected function getWith(): array
    {
        return [
            'municipality',
            'sector',
            'creator',
            'projectUsers.user',
            'updates.user',
            'updates.photos',
            'photos.uploader',
            'milestones',
        ];
    }

    protected function getCounts(): array
    {
        return ['updates'];
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

    /**
     * @return EloquentCollection<int, ProjectModel>
     */
    public function getMarkers(?int $municipalityId, ?int $provinceId): EloquentCollection
    {
        return $this->query(array_filter([
            'municipality_id' => $municipalityId,
            'province_id' => $municipalityId ? null : $provinceId,
        ], fn (mixed $value): bool => $value !== null))
            ->with(['sector:id,name', 'municipality:id,name'])
            ->whereNull('projects.deleted_at')
            ->orderBy('projects.name')
            ->limit(500)
            ->get();
    }

    /**
     * @return EloquentCollection<int, ProjectModel>
     */
    public function getReportProjects(?int $municipalityId): EloquentCollection
    {
        return ProjectModel::query()
            ->with(['sector:id,name'])
            ->when($municipalityId, fn ($query, $id) => $query->where('municipality_id', $id))
            ->get();
    }

    /**
     * @return Collection<string, string>
     */
    public function getLastUpdateDates(): Collection
    {
        return ProjectUpdate::query()
            ->selectRaw('project_id, MAX(update_date) as last_date')
            ->groupBy('project_id')
            ->pluck('last_date', 'project_id');
    }

    /**
     * @return Collection<int|string, Collection<int, User>>
     */
    public function getManagersByProject(): Collection
    {
        $rows = ProjectUser::query()
            ->where('role_in_project', ProjectUser::ROLE_MANAGER)
            ->with('user:id,name')
            ->get();

        return $rows->groupBy('project_id')->map(
            fn (EloquentCollection $projectRows): Collection => $projectRows
                ->map(fn (ProjectUser $row): ?User => $row->user)
                ->filter(fn (?User $user): bool => $user !== null)
                ->values(),
        );
    }

    public function loadUpdateFormRelations(ProjectModel $project): ProjectModel
    {
        return $project->load(['sector:id,name', 'municipality:id,name']);
    }

    public function saveProject(ProjectModel $project): void
    {
        $project->save();
    }

    public function changeStatus(ProjectModel $project, string $status, ?int $userId, ?string $note): ProjectModel
    {
        return $project->changeStatus($status, $userId, $note);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createUpdate(array $data): ProjectUpdate
    {
        return ProjectUpdate::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPhoto(array $data): ProjectPhoto
    {
        return ProjectPhoto::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createMilestone(ProjectModel $project, array $data): ProjectMilestone
    {
        return ProjectMilestone::create([
            ...$data,
            'project_id' => $project->id,
            'order' => $project->milestones()->count() + 1,
        ]);
    }

    public function deleteMilestone(int $projectId, int $milestoneId): void
    {
        ProjectMilestone::query()
            ->where('project_id', $projectId)
            ->findOrFail($milestoneId)
            ->delete();
    }

    public function updateMilestoneStatus(int $projectId, int $milestoneId, string $status): void
    {
        $milestone = ProjectMilestone::query()
            ->where('project_id', $projectId)
            ->findOrFail($milestoneId);

        if ($status === ProjectMilestone::STATUS_COMPLETED) {
            $milestone->markCompleted();

            return;
        }

        $milestone->update([
            'status' => $status,
            'completed_date' => null,
        ]);
    }
}
