<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sector;
use App\Modules\Sector\Domain\Services\ManageSectorsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SectorController extends Controller
{
    public function __construct(private readonly ManageSectorsService $service) {}

    public function index(Request $request): Response
    {
        $status = $request->input('status', 'active');

        return Inertia::render('admin/sectors', [
            ...$this->service->index($request->input('search'), $status),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'municipality_id' => ['required', 'integer', 'exists:municipalities,id'],
            'name' => ['required', 'string', 'max:255'],
            'geojson_polygon' => ['nullable', 'json'],
        ]);

        if (! empty($validated['geojson_polygon'])) {
            $validated['geojson_polygon'] = json_decode($validated['geojson_polygon'], true);
        } else {
            unset($validated['geojson_polygon']);
        }

        $this->service->create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sector creado.')]);

        return back();
    }

    public function update(Request $request, Sector $sector): RedirectResponse
    {
        $validated = $request->validate([
            'municipality_id' => ['required', 'integer', 'exists:municipalities,id'],
            'name' => ['required', 'string', 'max:255'],
            'geojson_polygon' => ['nullable', 'json'],
        ]);

        if (! empty($validated['geojson_polygon'])) {
            $validated['geojson_polygon'] = json_decode($validated['geojson_polygon'], true);
        } else {
            $validated['geojson_polygon'] = null;
        }

        $this->service->update($sector, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sector actualizado.')]);

        return back();
    }

    public function destroy(Sector $sector): RedirectResponse
    {
        $this->service->delete($sector);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sector desactivado.')]);

        return back();
    }
}
