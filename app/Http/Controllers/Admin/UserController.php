<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Municipality;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::query()
            ->withTrashed()
            ->with([
                'municipality' => fn ($q) => $q->withTrashed(),
                'sector' => fn ($q) => $q->withTrashed(),
            ]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $status = $request->input('status', 'active');
        if ($status === 'active') {
            $query->whereNull('deleted_at');
        } elseif ($status === 'inactive') {
            $query->whereNotNull('deleted_at');
        }

        $users = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('admin/users', [
            'users' => $users,
            'municipalities' => Municipality::query()
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'name']),
            'sectors' => Sector::query()
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'municipality_id', 'name']),
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

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

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

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Usuario actualizado.')]);

        return back();
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Usuario desactivado.')]);

        return back();
    }
}
