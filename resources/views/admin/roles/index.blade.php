@extends('admin.layouts.app')

@section('title', 'Roles')
@section('page-title', 'Roles & Permissions')

@section('content')
<div class="row g-4">
    <div class="col-lg-5">
        <div class="glass-card p-4">
            <h5 class="text-white mb-3">Create Role</h5>
            <form method="POST" action="{{ route('admin.roles.store') }}">@csrf
                <div class="mb-3"><label class="form-label">Role Name</label><input name="name" class="form-control" required></div>
                <div class="mb-3">
                    <label class="form-label">Permissions</label>
                    @forelse($permissions as $permission)
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="np_{{ $permission->id }}"><label class="form-check-label" for="np_{{ $permission->id }}">{{ $permission->name }}</label></div>
                    @empty
                        <p class="text-muted-admin small">No permissions defined. Run seeder or create via Spatie.</p>
                    @endforelse
                </div>
                <button class="btn btn-admin-primary">Create Role</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="glass-card">
            <div class="table-responsive">
                <table class="table table-admin mb-0">
                    <thead><tr><th>Role</th><th>Users</th><th>Permissions</th><th></th></tr></thead>
                    <tbody>
                        @forelse($roles as $role)
                            <tr>
                                <td>{{ $role->name }}</td>
                                <td>{{ $role->users_count }}</td>
                                <td class="small">{{ $role->permissions->pluck('name')->join(', ') ?: '—' }}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-light" data-bs-toggle="collapse" data-bs-target="#role-{{ $role->id }}">Edit</button>
                                </td>
                            </tr>
                            <tr class="collapse" id="role-{{ $role->id }}">
                                <td colspan="4" class="bg-transparent">
                                    <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="p-3 border border-secondary rounded">@csrf @method('PUT')
                                        <div class="mb-2"><input name="name" class="form-control form-control-sm" value="{{ $role->name }}" required></div>
                                        @foreach($permissions as $permission)
                                            <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked($role->permissions->contains('name', $permission->name))><label class="form-check-label small">{{ $permission->name }}</label></div>
                                        @endforeach
                                        <button class="btn btn-sm btn-admin-primary mt-2">Update</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted-admin">No roles yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
