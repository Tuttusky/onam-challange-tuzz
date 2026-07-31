@extends('admin.layouts.app')

@section('title', 'Badges')
@section('page-title', 'Badges')

@section('content')
<div class="d-flex justify-content-between mb-4">
    <h2 class="page-title mb-0">Badges</h2>
    <a href="{{ route('admin.badges.create') }}" class="btn btn-admin-primary">New Badge</a>
</div>
<div class="glass-card">
    <div class="table-responsive">
        <table class="table table-admin mb-0">
            <thead><tr><th>Name</th><th>Campaign</th><th>Match Range</th><th>Active</th><th></th></tr></thead>
            <tbody>
                @forelse($badges as $badge)
                    <tr>
                        <td>{{ $badge->name }}</td>
                        <td>{{ $badge->campaign?->name ?? 'Global' }}</td>
                        <td>{{ $badge->min_match_percent }}% – {{ $badge->max_match_percent }}%</td>
                        <td>{{ $badge->is_active ? 'Yes' : 'No' }}</td>
                        <td>
                            <a href="{{ route('admin.badges.edit', $badge) }}" class="btn btn-sm btn-outline-light">Edit</a>
                            <form action="{{ route('admin.badges.destroy', $badge) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted-admin">No badges yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($badges->hasPages())<div class="p-3">{{ $badges->links() }}</div>@endif
</div>
@endsection
