<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Municipality;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectPhoto;
use App\Models\ProjectUpdate;
use App\Models\ProjectUser;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    private const TRANSITIONS = [
        Project::STATUS_PLANNED => [Project::STATUS_IN_PROGRESS, Project::STATUS_CANCELLED],
        Project::STATUS_IN_PROGRESS => [Project::STATUS_PAUSED, Project::STATUS_COMPLETED, Project::STATUS_CANCELLED],
        Project::STATUS_PAUSED => [Project::STATUS_IN_PROGRESS, Project::STATUS_CANCELLED],
        Project::STATUS_COMPLETED => [Project::STATUS_IN_PROGRESS],
        Project::STATUS_CANCELLED => [],
    ];

    public function index(Request $request): Response
    {
        $request->validate([
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
        ]);

        $municipalityId = $this->municipalityId($request);

        $query = Project::query()
            ->with(['sector:id,name', 'municipality:id,name'])
            ->when($municipalityId, fn ($q) => $q->where('municipality_id', $municipalityId));

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('contractor_name', 'like', "%{$search}%")
                    ->orWhere('address_text', 'like', "%{$search}%");
            });
        }

        if ($type = array_filter((array) $request->input('type', []))) {
            $query->whereIn('type', $type);
        }

        if ($status = array_filter((array) $request->input('status', []))) {
            $query->whereIn('status', $status);
        }

        if ($sectorId = array_filter((array) $request->input('sector_id', []))) {
            $query->whereIn('sector_id', $sectorId);
        }

        if ($from = $request->input('from')) {
            $query->whereDate('start_date_planned', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('end_date_planned', '<=', $to);
        }

        $projects = $query
            ->withCount('updates')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('admin/projects/index', [
            'projects' => $projects,
            'sectors' => $this->sectorOptions($municipalityId),
            'types' => $this->typeOptions(),
            'statuses' => $this->statusOptions(),
            'municipalities' => $this->municipalityOptions($request),
            'filters' => $request->only([
                'search',
                'type',
                'status',
                'sector_id',
                'from',
                'to',
                'municipality_id',
            ]),
            'stats' => $this->stats($municipalityId),
        ]);
    }

    public function create(Request $request): Response
    {
        $municipalityId = $this->municipalityId($request);

        return Inertia::render('admin/projects/create', [
            'municipalities' => $this->municipalityOptions($request),
            'defaultMunicipality' => $this->municipality($request, $municipalityId),
            'sectors' => $this->allSectorOptions(),
            'types' => $this->typeOptions(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $municipalityId = $this->municipalityId($request, required: true);

        $validated = $request->validate($this->storeRules($municipalityId));

        $project = Project::create([
            ...$validated,
            'municipality_id' => $municipalityId,
            'status' => Project::STATUS_PLANNED,
            'progress_percentage' => 0,
            'budget_executed' => 0,
            'created_by' => $request->user()?->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Obra creada correctamente.')]);

        return redirect("/admin/projects/{$project->id}");
    }

    public function show(Request $request, int $project): Response
    {
        $project = $this->scopedQuery($request)
            ->with([
                'municipality:id,name,province_id',
                'sector:id,name,geojson_polygon',
                'creator:id,name',
                'projectUsers.user:id,name',
                'updates' => fn ($q) => $q->with(['user:id,name', 'photos']),
                'photos' => fn ($q) => $q->with('uploader:id,name'),
                'milestones',
            ])
            ->findOrFail($project);

        $municipalityId = $this->municipalityId($request);

        return Inertia::render('admin/projects/show', [
            'project' => $project,
            'users' => $this->userOptions($municipalityId),
            'roles' => $this->roleOptions(),
            'statuses' => $this->statusOptions(),
            'municipalities' => $this->municipalityOptions($request),
            'map' => $this->mapPayload($request, $project),
        ]);
    }

    public function edit(Request $request, int $project): Response
    {
        $project = $this->scopedQuery($request)->findOrFail($project);
        $municipalityId = $this->municipalityId($request);

        return Inertia::render('admin/projects/edit', [
            'project' => $project,
            'municipalities' => $this->municipalityOptions($request),
            'defaultMunicipality' => $this->municipality(
                $request,
                $municipalityId ?? (int) $project->municipality_id,
            ),
            'sectors' => $this->allSectorOptions(),
            'types' => $this->typeOptions(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, int $project): RedirectResponse
    {
        $project = $this->scopedQuery($request)->findOrFail($project);
        $municipalityId = $this->municipalityId($request) ?? (int) $project->municipality_id;

        $validated = $request->validate($this->storeRules($municipalityId));

        $project->update([
            ...$validated,
            'municipality_id' => $municipalityId,
            'updated_by' => $request->user()?->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Obra actualizada.')]);

        return redirect("/admin/projects/{$project->id}");
    }

    public function destroy(Request $request, int $project): RedirectResponse
    {
        $project = $this->scopedQuery($request)->findOrFail($project);
        $project->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Obra desactivada.')]);

        return redirect('/admin/projects');
    }

    public function updateStatus(Request $request, int $project): RedirectResponse
    {
        $project = $this->scopedQuery($request)->findOrFail($project);

        $validated = $request->validate([
            'status' => ['required', Rule::in(self::TRANSITIONS[$project->status] ?? [])],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $project->changeStatus(
            $validated['status'],
            $request->user()?->id,
            $validated['note'] ?? null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Estado actualizado.')]);

        return back();
    }

    public function storeAssignment(Request $request, int $project): RedirectResponse
    {
        $project = $this->scopedQuery($request)->findOrFail($project);
        $municipalityId = $this->municipalityId($request);

        $validated = $request->validate([
            'user_id' => [
                'required',
                'integer',
                $this->userExistsRule($municipalityId),
            ],
            'role_in_project' => ['required', Rule::in(ProjectUser::ROLES)],
        ]);

        ProjectUser::updateOrCreate(
            ['project_id' => $project->id, 'user_id' => $validated['user_id']],
            ['role_in_project' => $validated['role_in_project'], 'assigned_at' => now()],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Equipo asignado.')]);

        return back();
    }

    public function destroyAssignment(Request $request, int $project, int $projectUser): RedirectResponse
    {
        $project = $this->scopedQuery($request)->findOrFail($project);

        $assignment = ProjectUser::query()
            ->where('project_id', $project->id)
            ->findOrFail($projectUser);

        $assignment->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Integrante retirado del equipo.')]);

        return back();
    }

    public function updateCreate(Request $request, int $project): Response
    {
        $project = $this->scopedQuery($request)->findOrFail($project);

        return Inertia::render('admin/projects/create-update', [
            'project' => $project->load(['sector:id,name', 'municipality:id,name']),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function storeUpdate(Request $request, int $project): RedirectResponse
    {
        $project = $this->scopedQuery($request)->findOrFail($project);

        $validated = $request->validate([
            'update_date' => ['required', 'date'],
            'progress_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', Rule::in(Project::STATUSES)],
            'budget_spent' => ['nullable', 'numeric', 'min:0'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['nullable', 'image', 'max:8192'],
            'captions' => ['nullable', 'array', 'max:10'],
            'captions.*' => ['nullable', 'string', 'max:255'],
            'taken_at' => ['nullable', 'date'],
        ]);

        $photos = array_values(array_filter($request->file('photos', []) ?? []));
        $captions = array_values(array_filter($request->input('captions', []) ?? []));

        $newStatus = $validated['status'] ?? null;
        $progress = (int) $validated['progress_percentage'];

        if ($progress >= 100 && $newStatus === null) {
            $newStatus = Project::STATUS_COMPLETED;
        }

        $project->progress_percentage = $newStatus === Project::STATUS_COMPLETED ? 100 : $progress;
        $project->budget_executed = $this->applyBudget($project, (float) ($validated['budget_spent'] ?? 0));

        if ($newStatus !== null && $newStatus !== $project->status) {
            if ($newStatus === Project::STATUS_COMPLETED) {
                $project->end_date_real = $project->end_date_real ?? $validated['update_date'];
                $project->status = Project::STATUS_COMPLETED;
            } else {
                if ($newStatus === Project::STATUS_IN_PROGRESS && $project->start_date_real === null) {
                    $project->start_date_real = $validated['update_date'];
                }
                $project->status = $newStatus;
            }
        }

        $project->save();

        $update = ProjectUpdate::create([
            'project_id' => $project->id,
            'user_id' => $request->user()?->id,
            'update_date' => $validated['update_date'],
            'progress_percentage_at_update' => $project->progress_percentage,
            'description' => $validated['description'] ?? null,
            'status_at_update' => $project->status,
            'budget_spent_at_update' => $validated['budget_spent'] ?? null,
        ]);

        foreach ($photos as $index => $photo) {
            $path = $photo->store('projects', 'public');

            if (! $path) {
                continue;
            }

            ProjectPhoto::create([
                'project_id' => $project->id,
                'project_update_id' => $update->id,
                'photo_url' => Storage::url($path),
                'caption' => $captions[$index] ?? null,
                'taken_at' => $validated['taken_at'] ?? $validated['update_date'],
                'uploaded_by' => $request->user()?->id,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Avance registrado.')]);

        return redirect("/admin/projects/{$project->id}");
    }

    public function storeMilestone(Request $request, int $project): RedirectResponse
    {
        $project = $this->scopedQuery($request)->findOrFail($project);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'planned_date' => ['nullable', 'date'],
        ]);

        ProjectMilestone::create([
            'project_id' => $project->id,
            'name' => $validated['name'],
            'order' => $project->milestones()->count() + 1,
            'planned_date' => $validated['planned_date'] ?? null,
            'status' => ProjectMilestone::STATUS_PENDING,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Hito agregado.')]);

        return back();
    }

    public function updateMilestoneStatus(Request $request, int $project, int $milestone): RedirectResponse
    {
        $project = $this->scopedQuery($request)->findOrFail($project);

        $milestone = ProjectMilestone::query()
            ->where('project_id', $project->id)
            ->findOrFail($milestone);

        $validated = $request->validate([
            'status' => ['required', Rule::in(ProjectMilestone::STATUSES)],
        ]);

        if ($validated['status'] === ProjectMilestone::STATUS_COMPLETED) {
            $milestone->markCompleted();
        } else {
            $milestone->update([
                'status' => $validated['status'],
                'completed_date' => null,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Hito actualizado.')]);

        return back();
    }

    public function destroyMilestone(Request $request, int $project, int $milestone): RedirectResponse
    {
        $project = $this->scopedQuery($request)->findOrFail($project);

        ProjectMilestone::query()
            ->where('project_id', $project->id)
            ->findOrFail($milestone)
            ->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Hito eliminado.')]);

        return back();
    }

    /**
     * @return array<string, array<int, string|Rule>>
     */
    private function storeRules(?int $municipalityId): array
    {
        return [
            'sector_id' => [
                'nullable',
                'integer',
                Rule::exists('sectors', 'id')->where('municipality_id', $municipalityId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(Project::TYPES)],
            'description' => ['nullable', 'string', 'max:5000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'address_text' => ['nullable', 'string', 'max:255'],
            'budget_assigned' => ['nullable', 'numeric', 'min:0'],
            'start_date_planned' => ['nullable', 'date'],
            'end_date_planned' => ['nullable', 'date'],
            'contractor_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return Exists
     */
    private function userExistsRule(?int $municipalityId)
    {
        $rule = Rule::exists('users', 'id');

        if ($municipalityId) {
            $rule->where('municipality_id', $municipalityId);
        }

        return $rule;
    }

    private function applyBudget(Project $project, float $spent): float
    {
        $executed = (float) $project->budget_executed + $spent;
        $assigned = (float) $project->budget_assigned;

        return $assigned > 0 ? round(min($executed, $assigned), 2) : round($executed, 2);
    }

    private function municipalityId(Request $request, bool $required = false): ?int
    {
        $user = $request->user();
        $userMunicipalityId = $user?->municipality_id ? (int) $user->municipality_id : null;

        if ($userMunicipalityId) {
            return $userMunicipalityId;
        }

        $filtered = $request->integer('municipality_id');

        if ($filtered > 0) {
            return $filtered;
        }

        if ($required) {
            abort(422, __('Selecciona un municipio.'));
        }

        return null;
    }

    /**
     * @return Builder<Project>
     */
    private function scopedQuery(Request $request)
    {
        $municipalityId = $this->municipalityId($request);

        return Project::query()
            ->when($municipalityId, fn ($q) => $q->where('municipality_id', $municipalityId));
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function sectorOptions(?int $municipalityId): array
    {
        return Sector::query()
            ->whereNull('deleted_at')
            ->when($municipalityId, fn ($q) => $q->where('municipality_id', $municipalityId))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    /**
     * @return array<int, array{id: int, municipality_id: int, name: string}>
     */
    private function allSectorOptions(): array
    {
        return Sector::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'municipality_id', 'name'])
            ->toArray();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function municipalityOptions(Request $request): array
    {
        if ($request->user()?->municipality_id) {
            return Municipality::query()
                ->where('id', $request->user()->municipality_id)
                ->get(['id', 'name'])
                ->toArray();
        }

        return Municipality::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private function municipality(Request $request, ?int $municipalityId): ?array
    {
        $id = $request->user()?->municipality_id ? (int) $request->user()->municipality_id : $municipalityId;

        if (! $id) {
            return null;
        }

        return Municipality::query()->find($id, ['id', 'name'])?->toArray();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function userOptions(?int $municipalityId): array
    {
        return User::query()
            ->whereNull('deleted_at')
            ->when($municipalityId, fn ($q) => $q->where('municipality_id', $municipalityId))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function typeOptions(): array
    {
        return collect(Project::TYPES)
            ->map(fn (string $type) => ['value' => $type, 'label' => Project::typeLabelFor($type)])
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return collect(Project::STATUSES)
            ->map(fn (string $status) => ['value' => $status, 'label' => Project::statusLabelFor($status)])
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function roleOptions(): array
    {
        return collect(ProjectUser::ROLES)
            ->map(fn (string $role) => ['value' => $role, 'label' => ProjectUser::roleLabelFor($role)])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function stats(?int $municipalityId): array
    {
        $query = Project::query()->when($municipalityId, fn ($q) => $q->where('municipality_id', $municipalityId));

        $projects = $query->select(['status', 'budget_assigned', 'budget_executed'])->get();

        $byStatus = collect(Project::STATUSES)->mapWithKeys(fn (string $status) => [$status => 0])->all();

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

    /**
     * @return array<string, mixed>
     */
    private function mapPayload(Request $request, Project $project): array
    {
        $municipalityId = $this->municipalityId($request) ?? $project->municipality_id;

        $sectors = Sector::query()
            ->where('municipality_id', $municipalityId)
            ->get(['id', 'name', 'geojson_polygon'])
            ->toArray();

        $municipality = Municipality::query()->find($municipalityId, ['id', 'name', 'province_id', 'geojson_polygon']);

        return [
            'municipality' => $municipality?->toArray() ?: null,
            'municipalities' => $municipality ? [$municipality->toArray()] : [],
            'sectors' => $sectors,
        ];
    }
}
