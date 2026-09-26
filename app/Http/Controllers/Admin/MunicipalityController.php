<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Municipality;
use App\Models\Province;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MunicipalityController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Municipality::query()
            ->withTrashed()
            ->with(['province' => fn ($q) => $q->withTrashed()]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('domain', 'like', "%{$search}%")
                    ->orWhere('subdomain', 'like', "%{$search}%");
            });
        }

        $status = $request->input('status', 'active');
        if ($status === 'active') {
            $query->whereNull('deleted_at');
        } elseif ($status === 'inactive') {
            $query->whereNotNull('deleted_at');
        }

        $municipalities = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('admin/municipalities', [
            'municipalities' => $municipalities,
            'provinces' => Province::query()
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'name']),
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

        Municipality::create($validated);

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

        $municipality->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Municipio actualizado.')]);

        return back();
    }

    public function destroy(Municipality $municipality): RedirectResponse
    {
        $municipality->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Municipio desactivado.')]);

        return back();
    }
}
