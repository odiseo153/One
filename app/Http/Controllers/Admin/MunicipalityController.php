<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Municipality;
use App\Modules\Municipality\Domain\Services\ManageMunicipalitiesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MunicipalityController extends Controller
{
    public function __construct(private readonly ManageMunicipalitiesService $service) {}

    public function index(Request $request): Response
    {
        $status = $request->input('status', 'active');

        return Inertia::render('admin/municipalities', [
            ...$this->service->index($request->input('search'), $status),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'logo_url' => ['nullable', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
            'subdomain' => ['nullable', 'string', 'max:255'],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'status' => ['nullable', 'string', 'max:255'],
            'registration_date' => ['nullable', 'date'],
            'contracted_plan' => ['nullable', 'string', 'max:255'],
        ]);

        $this->service->create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Municipio creado.')]);

        return back();
    }

    public function update(Request $request, Municipality $municipality): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'logo_url' => ['nullable', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
            'subdomain' => ['nullable', 'string', 'max:255'],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'status' => ['nullable', 'string', 'max:255'],
            'registration_date' => ['nullable', 'date'],
            'contracted_plan' => ['nullable', 'string', 'max:255'],
        ]);

        $this->service->update($municipality, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Municipio actualizado.')]);

        return back();
    }

    public function destroy(Municipality $municipality): RedirectResponse
    {
        $this->service->delete($municipality);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Municipio desactivado.')]);

        return back();
    }
}
