@extends('admin.layouts.app')

@section('title', 'Banners')
@section('page-title', 'Banners')

@section('content')
<div class="d-flex justify-content-between mb-4">
    <h2 class="page-title mb-0">Banners</h2>
    <a href="{{ route('admin.banners.create') }}" class="btn btn-admin-primary">New Banner</a>
</div>
<div class="glass-card">
    <div class="table-responsive">
        <table class="table table-admin mb-0">
            <thead><tr><th>Title</th><th>Type</th><th>Active</th><th>Schedule</th><th></th></tr></thead>
            <tbody>
                @forelse($banners as $banner)
                    <tr>
                        <td>{{ $banner->title }}</td>
                        <td>{{ ucfirst($banner->type) }}</td>
                        <td>{{ $banner->is_active ? 'Yes' : 'No' }}</td>
                        <td class="small">{{ $banner->starts_at?->format('M d') ?? '—' }} – {{ $banner->ends_at?->format('M d') ?? '—' }}</td>
                        <td>
                            <a href="{{ route('admin.banners.edit', $banner) }}" class="btn btn-sm btn-outline-light">Edit</a>
                            <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete banner?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted-admin">No banners yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($banners->hasPages())<div class="p-3">{{ $banners->links() }}</div>@endif
</div>
@endsection
