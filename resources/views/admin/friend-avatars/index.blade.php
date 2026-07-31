@extends('admin.layouts.app')

@section('title', 'Friend Avatars')
@section('page-title', 'Friend Avatars')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-white-50 mb-0">Curated avatars for friend challenge personalization.</p>
    <a href="{{ route('admin.friend-avatars.create') }}" class="btn btn-admin-primary">Add Avatar</a>
</div>

<div class="glass-card p-0 overflow-hidden">
    <table class="table table-dark table-hover mb-0">
        <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Path</th>
                <th>Order</th>
                <th>Active</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($avatars as $avatar)
                <tr>
                    <td>{{ $avatar->name }}</td>
                    <td><code>{{ $avatar->slug }}</code></td>
                    <td class="small">{{ $avatar->path }}</td>
                    <td>{{ $avatar->sort_order }}</td>
                    <td>{{ $avatar->is_active ? 'Yes' : 'No' }}</td>
                    <td class="text-end">
                        <a href="{{ route('admin.friend-avatars.edit', $avatar) }}" class="btn btn-sm btn-glass">Edit</a>
                        <form action="{{ route('admin.friend-avatars.destroy', $avatar) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this avatar?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-white-50 py-4">No avatars yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">{{ $avatars->links() }}</div>
@endsection
