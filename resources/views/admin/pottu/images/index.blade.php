@extends('admin.layouts.app')

@section('title', 'Girl Images')
@section('page-title', 'Girl Images — ' . $campaign->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="btn btn-outline-light btn-sm">Campaign</a>
        <a href="{{ route('admin.campaigns.pottu-styles.index', $campaign) }}" class="btn btn-outline-light btn-sm">Pottu Styles</a>
        <a href="{{ route('admin.campaigns.pottu-settings.edit', $campaign) }}" class="btn btn-outline-light btn-sm">Settings</a>
    </div>
    <a href="{{ route('admin.campaigns.pottu-images.create', $campaign) }}" class="btn btn-admin-primary">Add Image</a>
</div>

<div class="glass-card">
    <div class="table-responsive">
        <table class="table table-admin mb-0">
            <thead>
                <tr>
                    <th>Preview</th>
                    <th>Title</th>
                    <th>Path</th>
                    <th>Size</th>
                    <th>Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($images as $image)
                    <tr>
                        <td><img src="{{ $image->url }}" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:8px;"></td>
                        <td>
                            {{ $image->title }}
                            @if(str_contains($image->path, 'pottu-custom-images'))
                                <span class="badge bg-warning text-dark ms-1">Custom</span>
                            @endif
                        </td>
                        <td class="small text-muted-admin">{{ $image->path }}</td>
                        <td>{{ $image->width }}×{{ $image->height }}</td>
                        <td>{{ $image->is_active ? 'Yes' : 'No' }}</td>
                        <td>
                            <a href="{{ route('admin.campaigns.pottu-images.edit', [$campaign, $image]) }}" class="btn btn-sm btn-outline-light">Edit</a>
                            <form action="{{ route('admin.campaigns.pottu-images.destroy', [$campaign, $image]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted-admin py-4">No images yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $images->links() }}
@endsection
