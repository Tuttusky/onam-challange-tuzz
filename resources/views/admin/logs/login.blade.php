@extends('admin.layouts.app')

@section('title', 'Login Logs')
@section('page-title', 'Login Logs')

@section('content')
<div class="glass-card p-3 mb-3">
    <form method="GET" class="row g-2">
        <div class="col-md-4"><input name="email" class="form-control" placeholder="Email..." value="{{ request('email') }}"></div>
        <div class="col-md-3"><select name="success" class="form-select"><option value="">All</option><option value="1" @selected(request('success')==='1')>Success</option><option value="0" @selected(request('success')==='0')>Failed</option></select></div>
        <div class="col-md-2"><button class="btn btn-outline-light w-100">Filter</button></div>
    </form>
</div>
<div class="glass-card">
    <div class="table-responsive">
        <table class="table table-admin mb-0">
            <thead><tr><th>Email</th><th>Admin</th><th>Success</th><th>IP</th><th>Time</th></tr></thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->email }}</td>
                        <td>{{ $log->admin?->name ?? '—' }}</td>
                        <td><span class="badge {{ $log->success ? 'badge-status-active' : 'badge-status-inactive' }}">{{ $log->success ? 'Yes' : 'No' }}</span></td>
                        <td>{{ $log->ip }}</td>
                        <td>{{ $log->created_at?->format('M d, Y H:i:s') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted-admin">No login logs.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())<div class="p-3">{{ $logs->links() }}</div>@endif
</div>
@endsection
