<?php

namespace App\Modules\Project\Adapters\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectUser;
use App\Modules\Project\Domain\Services\CreateProjectService;
use App\Modules\Project\Domain\Services\DeleteProjectService;
use App\Modules\Project\Domain\Services\DestroyAssignmentService;
use App\Modules\Project\Domain\Services\DestroyMilestoneService;
use App\Modules\Project\Domain\Services\FindByIdProjectService;
use App\Modules\Project\Domain\Services\ListProjectsService;
use App\Modules\Project\Domain\Services\ProjectViewService;
use App\Modules\Project\Domain\Services\StoreAssignmentService;
use App\Modules\Project\Domain\Services\StoreMilestoneService;
use App\Modules\Project\Domain\Services\StoreUpdateService;
use App\Modules\Project\Domain\Services\UpdateMilestoneStatusService;
use App\Modules\Project\Domain\Services\UpdateProjectService;
use App\Modules\Project\Domain\Services\UpdateProjectStatusService;
use App\Modules\Project\Http\Requests\StoreProjectRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function __construct(
        private readonly CreateProjectService $createService,
        private readonly ListProjectsService $listService,
        private readonly FindByIdProjectService $findByIdService,
        private readonly UpdateProjectService $updateService,
        private readonly DeleteProjectService $deleteService,
        private readonly UpdateProjectStatusService $updateStatusService,
        private readonly StoreAssignmentService $storeAssignmentService,
        private readonly DestroyAssignmentService $destroyAssignmentService,
        private readonly StoreUpdateService $storeUpdateService,
        private readonly StoreMilestoneService $storeMilestoneService,
        private readonly UpdateMilestoneStatusService $updateMilestoneStatusService,
        private readonly DestroyMilestoneService $destroyMilestoneService,
        private readonly ProjectViewService $viewService,
    ) {}

    public function index(Request $request): Response
    {
        $municipalityId = $this->resolveMunicipalityId($request);

        $projects = $this->listService->execute(12);

        return Inertia::render('admin/projects/index', [
            'projects' => $projects,
            'sectors' => $this->viewService->sectorOptions($municipalityId),
            'types' => $this->viewService->typeOptions(),
            'statuses' => $this->viewService->statusOptions(),
            'municipalities' => $this->viewService->municipalityOptions($request->user()?->municipality_id),
            'filters' => $request->only([
                'search',
                'type',
                'status',
                'sector_id',
                'from',
                'to',
                'municipality_id',
            ]),
            'stats' => $this->viewService->stats($municipalityId),
        ]);
    }

    public function create(Request $request): Response
    {
        $municipalityId = $this->resolveMunicipalityId($request);

        return Inertia::render('admin/projects/create', [
            'municipalities' => $this->viewService->municipalityOptions($request->user()?->municipality_id),
            'defaultMunicipality' => $this->viewService->municipality($municipalityId),
            'sectors' => $this->viewService->allSectorOptions(),
            'types' => $this->viewService->typeOptions(),
            'statuses' => $this->viewService->statusOptions(),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $municipalityId = $this->resolveMunicipalityId($request, required: true);

        $data = array_merge($request->validated(), [
            'municipality_id' => $municipalityId,
            'status' => Project::STATUS_PLANNED,
            'progress_percentage' => 0,
            'budget_executed' => 0,
            'created_by' => $request->user()?->id,
        ]);

        $project = $this->createService->execute($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Obra creada correctamente.')]);

        return redirect("/admin/projects/{$project->id}");
    }

    public function show(Request $request, int $project): Response
    {
        $project = $this->findByIdService->execute($project);

        $municipalityId = $this->resolveMunicipalityId($request);

        return Inertia::render('admin/projects/show', [
            'project' => $project,
            'users' => $this->viewService->userOptions($municipalityId),
            'roles' => $this->viewService->roleOptions(),
            'statuses' => $this->viewService->statusOptions(),
            'municipalities' => $this->viewService->municipalityOptions($request->user()?->municipality_id),
            'map' => $this->viewService->map($municipalityId ?? $project->municipality_id),
        ]);
    }

    public function edit(Request $request, int $project): Response
    {
        $project = $this->findByIdService->execute($project);
        $municipalityId = $this->resolveMunicipalityId($request);

        return Inertia::render('admin/projects/edit', [
            'project' => $project,
            'municipalities' => $this->viewService->municipalityOptions($request->user()?->municipality_id),
            'defaultMunicipality' => $this->viewService->municipality(
                $municipalityId ?? (int) $project->municipality_id,
            ),
            'sectors' => $this->viewService->allSectorOptions(),
            'types' => $this->viewService->typeOptions(),
            'statuses' => $this->viewService->statusOptions(),
        ]);
    }

    public function update(StoreProjectRequest $request, int $project): RedirectResponse
    {
        $project = $this->findByIdService->execute($project);
        $municipalityId = $this->resolveMunicipalityId($request) ?? (int) $project->municipality_id;

        $data = array_merge($request->validated(), [
            'municipality_id' => $municipalityId,
            'updated_by' => $request->user()?->id,
        ]);

        $this->updateService->execute($project->id, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Obra actualizada.')]);

        return redirect("/admin/projects/{$project->id}");
    }

    public function destroy(Request $request, int $project): RedirectResponse
    {
        $project = $this->findByIdService->execute($project);
        $this->deleteService->execute($project->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Obra desactivada.')]);

        return redirect('/admin/projects');
    }

    public function updateStatus(Request $request, int $project): RedirectResponse
    {
        $project = $this->findByIdService->execute($project);

        $validated = $request->validate([
            'status' => ['required', Rule::in(UpdateProjectStatusService::getAllowedTransitions($project->status))],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->updateStatusService->execute($project->id, $validated, $request->user()?->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Estado actualizado.')]);

        return back();
    }

    public function storeAssignment(Request $request, int $project): RedirectResponse
    {
        $municipalityId = $this->resolveMunicipalityId($request);

        $validated = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->when($municipalityId, fn ($q) => $q->where('municipality_id', $municipalityId)),
            ],
            'role_in_project' => ['required', Rule::in(ProjectUser::ROLES)],
        ]);

        $this->storeAssignmentService->execute(
            $project,
            $validated['user_id'],
            $validated['role_in_project'],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Equipo asignado.')]);

        return back();
    }

    public function destroyAssignment(Request $request, int $project, int $projectUser): RedirectResponse
    {
        $this->destroyAssignmentService->execute($project, $projectUser);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Integrante retirado del equipo.')]);

        return back();
    }

    public function updateCreate(Request $request, int $project): Response
    {
        $project = $this->findByIdService->execute($project);

        return Inertia::render('admin/projects/create-update', [
            'project' => $this->viewService->loadUpdateFormRelations($project),
            'statuses' => $this->viewService->statusOptions(),
        ]);
    }

    public function storeUpdate(Request $request, int $project): RedirectResponse
    {
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

        $data = array_merge($validated, [
            'photos' => $request->file('photos', []),
            'captions' => $request->input('captions', []),
        ]);

        $this->storeUpdateService->execute($project, $data, $request->user()?->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Avance registrado.')]);

        return redirect("/admin/projects/{$project}");
    }

    public function storeMilestone(Request $request, int $project): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'planned_date' => ['nullable', 'date'],
        ]);

        $this->storeMilestoneService->execute($project, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Hito agregado.')]);

        return back();
    }

    public function updateMilestoneStatus(Request $request, int $project, int $milestone): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(ProjectMilestone::STATUSES)],
        ]);

        $this->updateMilestoneStatusService->execute($project, $milestone, $validated['status']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Hito actualizado.')]);

        return back();
    }

    public function destroyMilestone(Request $request, int $project, int $milestone): RedirectResponse
    {
        $this->destroyMilestoneService->execute($project, $milestone);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Hito eliminado.')]);

        return back();
    }

    private function resolveMunicipalityId(Request $request, bool $required = false): ?int
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
}
