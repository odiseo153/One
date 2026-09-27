<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\User\Domain\Services\ManageUsersService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private readonly ManageUsersService $service) {}

    public function index(Request $request): Response
    {
        $status = $request->input('status', 'active');

        return Inertia::render('admin/users', [
            ...$this->service->index($request->input('search'), $status),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
            'sector_id' => ['nullable', 'integer', 'exists:sectors,id'],
            'phone' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
        ]);

        $this->service->create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Usuario creado.')]);

        return back();
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
            'sector_id' => ['nullable', 'integer', 'exists:sectors,id'],
            'phone' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
        ]);

        $this->service->update($user, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Usuario actualizado.')]);

        return back();
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->service->delete($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Usuario desactivado.')]);

        return back();
    }
}
