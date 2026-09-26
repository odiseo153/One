<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Project;
use App\Modules\Business\Domain\Services\BusinessStatusService;
use App\Modules\Business\Domain\Services\DiscoverMapPlacesService;
use App\Modules\Business\Domain\Services\FilterBusinessMapService;
use App\Modules\Business\Domain\Services\ListBusinessMapService;
use App\Modules\Business\Domain\Services\StoreBusinessService;
use App\Modules\Business\Domain\Services\UpdateBusinessService;
use App\Modules\Business\Domain\Services\UpdateBusinessStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SectorMapController extends Controller
{
    public function __construct(
        private readonly ListBusinessMapService $listBusinessMapService,
        private readonly FilterBusinessMapService $filterBusinessMapService,
        private readonly DiscoverMapPlacesService $discoverMapPlacesService,
        private readonly StoreBusinessService $storeBusinessService,
        private readonly UpdateBusinessService $updateBusinessService,
        private readonly UpdateBusinessStatusService $updateBusinessStatusService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'filter' => ['nullable', 'array'],
            'filter.province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'filter.municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
            'filter.sector_id' => ['nullable', 'integer', 'exists:sectors,id'],
            'filter.registration_status' => ['nullable', Rule::in(BusinessStatusService::STATUSES)],
        ]);

        $payload = $this->listBusinessMapService->execute(
            $this->municipalityId($request),
            $filters['filter'] ?? [],
            $request->user()?->sector_id,
        );

        $payload['projects'] = $this->projectMarkers($request, $filters['filter'] ?? []);

        return Inertia::render('admin/sector-map', $payload);
    }

    public function businesses(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filter' => ['nullable', 'array'],
            'filter.sector_id' => ['nullable', 'integer', 'exists:sectors,id'],
            'filter.registration_status' => ['nullable', Rule::in(BusinessStatusService::STATUSES)],
            'polygon' => ['nullable', 'array'],
        ]);

        return response()->json(
            $this->filterBusinessMapService->execute(
                $this->municipalityId($request),
                $validated,
            ),
        );
    }

    public function places(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'query' => ['nullable', 'string', 'max:255'],
            'sector_id' => ['nullable', 'integer', 'exists:sectors,id'],
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
        ]);

        return response()->json(
            $this->discoverMapPlacesService->execute(
                $validated,
                $this->municipalityId($request),
                $request->user()?->sector_id,
            ),
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $this->storeBusinessService->execute(
            $this->validatedBusiness($request),
            $this->municipalityId($request),
            $request->user()?->sector_id,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Local creado.')]);

        return back();
    }

    public function update(Request $request, Business $business): RedirectResponse
    {
        $this->updateBusinessService->execute(
            $business,
            $this->validatedBusiness($request),
            $this->municipalityId($request),
            $request->user()?->sector_id,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Local actualizado.')]);

        return back();
    }

    public function updateStatus(Request $request, Business $business): JsonResponse
    {
        $validated = $request->validate([
            'registration_status' => ['required', Rule::in(BusinessStatusService::STATUSES)],
        ]);

        return response()->json([
            'data' => $this->updateBusinessStatusService->execute(
                $business,
                $validated['registration_status'],
                $this->municipalityId($request),
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedBusiness(Request $request): array
    {
        return $request->validate([
            'sector_id' => ['nullable', 'integer', 'exists:sectors,id'],
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address_text' => ['nullable', 'string', 'max:255'],
            'registration_status' => ['required', Rule::in(BusinessStatusService::STATUSES)],
            'rnc' => ['nullable', 'string', 'max:255'],
            'detected_at' => ['nullable', 'date'],
            'last_verified_at' => ['nullable', 'date'],
            'inspector_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
    }

    private function municipalityId(Request $request): ?int
    {
        return $request->user()?->municipality_id;
    }

    /**
     * Obras de infraestructura como marcadores adicionales del mapa.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function projectMarkers(Request $request, array $filters): array
    {
        $userMunicipalityId = $this->municipalityId($request);
        $municipalityFilter = isset($filters['municipality_id']) ? (int) $filters['municipality_id'] : 0;
        $provinceFilter = isset($filters['province_id']) ? (int) $filters['province_id'] : 0;
        $effectiveMunicipalityId = $municipalityFilter ?: $userMunicipalityId;

        $query = Project::query()
            ->with(['sector:id,name', 'municipality:id,name'])
            ->whereNull('projects.deleted_at')
            ->when($effectiveMunicipalityId, fn ($q) => $q->where('projects.municipality_id', $effectiveMunicipalityId))
            ->when(! $effectiveMunicipalityId && $provinceFilter, fn ($q) => $q->whereHas(
                'municipality',
                fn ($q) => $q->where('province_id', $provinceFilter),
            ));

        return $query
            ->orderBy('projects.name')
            ->limit(500)
            ->get()
            ->map(fn ($project) => [
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
