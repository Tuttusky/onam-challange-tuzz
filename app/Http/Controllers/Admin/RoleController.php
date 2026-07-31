<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()
            ->where('guard_name', 'admin')
            ->with('permissions')
            ->withCount('users')
            ->get();

        $permissions = Permission::query()
            ->where('guard_name', 'admin')
            ->orderBy('name')
            ->get();

        return view('admin.roles.index', compact('roles', 'permissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::query()->create([
            'name' => $data['name'],
            'guard_name' => 'admin',
        ]);

        if (! empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return back()->with('success', 'Role created successfully.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_unless($role->guard_name === 'admin', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,'.$role->id],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return back()->with('success', 'Role updated successfully.');
    }
}
