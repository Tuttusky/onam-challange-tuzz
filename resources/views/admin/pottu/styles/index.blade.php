@extends('admin.layouts.app')

@section('title', 'Pottu Styles')
@section('page-title', 'Pottu Styles — ' . $campaign->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('admin.campaigns.pottu-images.index', $campaign) }}" class="btn btn-outline-light btn-sm">Girl Images</a>
        <a href="{{ route('admin.campaigns.pottu-settings.edit', $campaign) }}" class="btn btn-outline-light btn-sm">Settings</a>
    </div>
    <a href="{{ route('admin.campaigns.pottu-styles.create', $campaign) }}" class="btn btn-admin-primary">Add Style</a>
</div>

<div class="glass-card">
    <div class="table-responsive">
        <table class="table table-admin mb-0">
            <thead><tr><th>Name</th><th>Type</th><th>Path</th><th>Size</th><th>Active</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($styles as $style)
                    <tr>
                        <td>{{ $style->name }}</td>
                        <td>{{ $style->type }}</td>
                        <td class="small text-muted-admin">{{ $style->path }}</td>
                        <td>{{ $style->default_size }}px</td>
                        <td>{{ $style->is_active ? 'Yes' : 'No' }}</td>
                        <td>
                            <a href="{{ route('admin.campaigns.pottu-styles.edit', [$campaign, $style]) }}" class="btn btn-sm btn-outline-light">Edit</a>
                            <form action="{{ route('admin.campaigns.pottu-styles.destroy', [$campaign, $style]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted-admin py-4">No styles yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $styles->links() }}
@endsection
