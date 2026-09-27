<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Modules\Business\Domain\Services\BusinessStatusService;
use App\Modules\Business\Domain\Services\DiscoverMapPlacesService;
use App\Modules\Business\Domain\Services\FilterBusinessMapService;
use App\Modules\Business\Domain\Services\ListBusinessCategoriesService;
use App\Modules\Business\Domain\Services\ListBusinessMapService;
use App\Modules\Business\Domain\Services\StoreBusinessService;
use App\Modules\Business\Domain\Services\UpdateBusinessService;
use App\Modules\Business\Domain\Services\UpdateBusinessStatusService;
use App\Modules\Business\Http\Requests\StoreBusinessRequest;
use App\Modules\Project\Domain\Services\ListProjectMarkersService;
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
        private readonly ListBusinessCategoriesService $listBusinessCategoriesService,
        private readonly FilterBusinessMapService $filterBusinessMapService,
        private readonly DiscoverMapPlacesService $discoverMapPlacesService,
        private readonly StoreBusinessService $storeBusinessService,
        private readonly UpdateBusinessService $updateBusinessService,
        private readonly UpdateBusinessStatusService $updateBusinessStatusService,
        private readonly ListProjectMarkersService $listProjectMarkersService,
    ) {}

    public function categories(): JsonResponse
    {
        return response()->json([
            'data' => $this->listBusinessCategoriesService->execute(),
        ]);
    }

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

        $payload['projects'] = $this->listProjectMarkersService->execute(
            $this->municipalityId($request),
            $filters['filter'] ?? [],
        );

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

    public function store(StoreBusinessRequest $request): RedirectResponse
    {
        $this->storeBusinessService->execute(
            $request->validated(),
            $this->municipalityId($request),
            $request->user()?->sector_id,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Local creado.')]);

        return back();
    }

    public function update(StoreBusinessRequest $request, Business $business): RedirectResponse
    {
        $this->updateBusinessService->execute(
            $business,
            $request->validated(),
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

    private function municipalityId(Request $request): ?int
    {
        return $request->user()?->municipality_id;
    }
}
