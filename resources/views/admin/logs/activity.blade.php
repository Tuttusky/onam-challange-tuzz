@extends('admin.layouts.app')

@section('title', 'Activity Logs')
@section('page-title', 'Activity Logs')

@section('content')
<div class="glass-card p-3 mb-3">
    <form method="GET" class="row g-2">
        <div class="col-md-4"><input name="search" class="form-control" placeholder="Search action..." value="{{ request('search') }}"></div>
        <div class="col-md-2"><button class="btn btn-outline-light w-100">Filter</button></div>
    </form>
</div>
<div class="glass-card">
    <div class="table-responsive">
        <table class="table table-admin mb-0">
            <thead><tr><th>Admin</th><th>Action</th><th>IP</th><th>Time</th></tr></thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->admin?->name ?? 'System' }}</td>
                        <td><code class="small">{{ $log->action }}</code></td>
                        <td>{{ $log->ip }}</td>
                        <td>{{ $log->created_at?->format('M d, Y H:i:s') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted-admin">No activity logs.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())<div class="p-3">{{ $logs->links() }}</div>@endif
</div>
@endsection
