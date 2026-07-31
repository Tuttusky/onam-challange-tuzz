@extends('admin.layouts.app')

@section('title', 'Backups')
@section('page-title', 'Database Backups')

@section('content')
<div class="d-flex justify-content-between mb-4">
    <h2 class="page-title mb-0">Backups</h2>
    <form method="POST" action="{{ route('admin.backups.create') }}">@csrf<button class="btn btn-admin-primary">Create Backup</button></form>
</div>
<div class="glass-card">
    <div class="table-responsive">
        <table class="table table-admin mb-0">
            <thead><tr><th>Filename</th><th>Size</th><th>Created By</th><th>Date</th></tr></thead>
            <tbody>
                @forelse($backups as $backup)
                    <tr>
                        <td><code>{{ $backup->filename }}</code></td>
                        <td>{{ number_format($backup->size / 1024, 1) }} KB</td>
                        <td>{{ $backup->creator?->name ?? 'System' }}</td>
                        <td>{{ $backup->created_at?->format('M d, Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted-admin">No backups yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($backups->hasPages())<div class="p-3">{{ $backups->links() }}</div>@endif
</div>
@endsection
