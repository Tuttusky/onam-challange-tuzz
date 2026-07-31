@extends('admin.layouts.app')

@section('title', 'CMS Pages')
@section('page-title', 'CMS Pages')

@section('content')
<div class="d-flex justify-content-between mb-4">
    <h2 class="page-title mb-0">CMS Pages</h2>
    <a href="{{ route('admin.cms.create') }}" class="btn btn-admin-primary">New Page</a>
</div>
<div class="glass-card">
    <div class="table-responsive">
        <table class="table table-admin mb-0">
            <thead><tr><th>Title</th><th>Slug</th><th>Published</th><th>Updated</th><th></th></tr></thead>
            <tbody>
                @forelse($pages as $page)
                    <tr>
                        <td>{{ $page->title }}</td>
                        <td><code>{{ $page->slug }}</code></td>
                        <td>{{ $page->is_published ? 'Yes' : 'No' }}</td>
                        <td>{{ $page->updated_at?->format('M d, Y') }}</td>
                        <td>
                            <a href="{{ route('admin.cms.edit', $page) }}" class="btn btn-sm btn-outline-light">Edit</a>
                            <form action="{{ route('admin.cms.destroy', $page) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete page?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted-admin">No pages yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($pages->hasPages())<div class="p-3">{{ $pages->links() }}</div>@endif
</div>
@endsection
