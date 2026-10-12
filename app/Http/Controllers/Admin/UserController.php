<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::with(['roles:id,name', 'permissions:id,name'])->orderBy('name')->paginate(15),
            'roles' => Role::pluck('name'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create', [
            'roles' => Role::whereNotIn('name', ['client', 'client-manager'])->pluck('name'),
            'permissions' => Permission::orderBy('name')->pluck('name'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(Role::whereNotIn('name', ['client', 'client-manager'])->pluck('name'))],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(Permission::pluck('name'))],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
        ]);

        // Admin-created accounts are pre-verified: the admin already vouches for the email.
        $user->forceFill(['email_verified_at' => now()])->save();

        $user->assignRole($data['role']);
        $user->syncPermissions($data['permissions'] ?? []);
        AuditLog::record($request->user(), 'user.created', "Utilizator creat: {$user->email}.", $user, ['role' => $data['role']]);

        return redirect()->route('admin.users.index')->with('success', 'Utilizator creat.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user->load(['roles:id,name', 'permissions:id,name']),
            'roles' => Role::pluck('name'),
            'permissions' => Permission::orderBy('name')->pluck('name'),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in(Role::pluck('name'))],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(Permission::pluck('name'))],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            ...(! empty($data['password']) ? ['password' => Hash::make($data['password'])] : []),
        ]);

        $user->syncRoles([$data['role']]);
        $user->syncPermissions($data['permissions'] ?? []);
        AuditLog::record($request->user(), 'user.updated', "Utilizator actualizat: {$user->email}.", $user, ['role' => $data['role'], 'password_changed' => ! empty($data['password'])]);

        return redirect()->route('admin.users.index')->with('success', 'Utilizator actualizat.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 422, 'Nu iti poti sterge propriul cont.');

        $user->delete();
        AuditLog::record($request->user(), 'user.deleted', "Utilizator sters: {$user->email}.", null, ['deleted_user_id' => $user->id]);

        return redirect()->route('admin.users.index')->with('success', 'Utilizator sters.');
    }
}
