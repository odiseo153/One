<?php

namespace App\Modules\Complaint\Adapters\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Complaint\Domain\Services\ManageComplaintsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ComplaintController extends Controller
{
    public function __construct(private readonly ManageComplaintsService $service) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'category', 'sector_id', 'assigned', 'from', 'to']);

        return Inertia::render('admin/complaints/index', [
            ...$this->service->index($this->municipalityId($request), $filters),
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, int $complaint): Response
    {
        $municipalityId = $this->municipalityId($request);

        return Inertia::render('admin/complaints/show', [
            'complaint' => $this->service->find($municipalityId, $complaint),
            'users' => $this->service->users($municipalityId),
            'statuses' => $this->service->statuses(),
        ]);
    }

    public function assign(Request $request, int $complaint): RedirectResponse
    {
        $municipalityId = $this->municipalityId($request);
        $validated = $request->validate([
            'assigned_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('municipality_id', $municipalityId)],
        ]);

        $this->service->assign($municipalityId, $complaint, $validated['assigned_user_id'] ?? null);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Queja actualizada.')]);

        return back();
    }

    public function updateStatus(Request $request, int $complaint): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->service->updateStatus(
            $this->municipalityId($request),
            $complaint,
            $validated['status'],
            $request->user()?->id,
            $validated['note'] ?? null,
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Estado actualizado.')]);

        return back();
    }

    private function municipalityId(Request $request): ?int
    {
        return $request->user()?->municipality_id;
    }
}
