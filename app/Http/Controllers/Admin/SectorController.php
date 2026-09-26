<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Municipality;
use App\Models\Sector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SectorController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Sector::query()
            ->withTrashed()
            ->with(['municipality' => fn ($q) => $q->withTrashed()]);

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $status = $request->input('status', 'active');
        if ($status === 'active') {
            $query->whereNull('deleted_at');
        } elseif ($status === 'inactive') {
            $query->whereNotNull('deleted_at');
        }

        $sectors = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('admin/sectors', [
            'sectors' => $sectors,
            'municipalities' => Municipality::query()
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'name']),
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

        Sector::create($validated);

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

        $sector->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sector actualizado.')]);

        return back();
    }

    public function destroy(Sector $sector): RedirectResponse
    {
        $sector->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sector desactivado.')]);

        return back();
    }
}
