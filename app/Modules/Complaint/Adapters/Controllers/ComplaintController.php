<?php

namespace App\Modules\Complaint\Adapters\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Complaint\Domain\Services\PublicComplaintsService;
use App\Modules\Complaint\Http\Requests\StoreComplaintRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ComplaintController extends Controller
{
    public function __construct(private readonly PublicComplaintsService $service) {}

    public function create(Request $request): Response
    {
        return Inertia::render('complaints/create', [
            ...$this->service->formData(),
            'municipality_id' => $request->integer('municipality_id') ?: null,
        ]);
    }

    public function store(StoreComplaintRequest $request): RedirectResponse
    {
        $complaint = $this->service->create($request->validated(), $request->file('photo'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Queja registrada. Guarda tu código de seguimiento.'),
        ]);

        return redirect()->route('complaints.show', $complaint->tracking_code);
    }

    public function show(Request $request, string $trackingCode): Response
    {
        return Inertia::render('complaints/show', [
            'complaint' => $this->service->findPublic($trackingCode),
        ]);
    }

    public function track(): Response
    {
        return Inertia::render('complaints/track');
    }
}
