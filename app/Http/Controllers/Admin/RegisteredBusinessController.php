<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Business\Domain\Services\ListRegisteredBusinessesService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredBusinessController extends Controller
{
    public function __construct(
        private readonly ListRegisteredBusinessesService $listRegisteredBusinessesService,
    ) {}

    public function index(Request $request): Response
    {
        $request->validate([
            'filter' => ['nullable', 'array'],
            'filter.search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return Inertia::render(
            'admin/registered-businesses',
            $this->listRegisteredBusinessesService->execute(
                (int) $request->input('per_page', 15),
                $request->only(['filter', 'sort']),
            ),
        );
    }
}
