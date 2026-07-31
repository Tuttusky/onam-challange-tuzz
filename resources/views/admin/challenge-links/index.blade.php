@extends('admin.layouts.app')

@section('title', 'Challenge Links')
@section('page-title', 'Challenge Links')

@section('content')
<div class="glass-card">
    <div class="table-responsive">
        <table class="table table-admin mb-0">
            <thead><tr><th>ID</th><th>Code</th><th>Image</th><th>Campaign</th><th>Creator</th><th>Shares</th><th>Active</th><th></th></tr></thead>
            <tbody>
                @forelse($links as $link)
                    <tr>
                        <td>#{{ $link->id }}</td>
                        <td><code>{{ $link->code }}</code></td>
                        <td>
                            @if($link->pottuImage)
                                <img src="{{ $link->pottuImage->url }}" alt="" style="width:40px;height:52px;object-fit:cover;border-radius:6px;">
                                @if(str_contains($link->pottuImage->path, 'pottu-custom-images'))
                                    <span class="badge bg-warning text-dark ms-1">Custom</span>
                                @endif
                            @else
                                <span class="text-muted-admin">—</span>
                            @endif
                        </td>
                        <td>{{ $link->campaign?->name }}</td>
                        <td>{{ $link->creatorSession?->player?->name ?? '—' }}</td>
                        <td>{{ $link->share_count }}</td>
                        <td>{{ $link->is_active ? 'Yes' : 'No' }}</td>
                        <td><a href="{{ route('admin.challenge-links.show', $link) }}" class="btn btn-sm btn-outline-light">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-muted-admin">No challenge links.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($links->hasPages())<div class="p-3">{{ $links->links() }}</div>@endif
</div>
@endsection
