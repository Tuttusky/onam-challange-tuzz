@extends('admin.layouts.app')

@section('title', 'Campaigns')
@section('page-title', 'Campaigns')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="page-title mb-0">Campaigns</h2>
    <a href="{{ route('admin.campaigns.create') }}" class="btn btn-admin-primary">New Campaign</a>
</div>

<div class="glass-card p-3 mb-4">
    <form method="GET" class="row g-2">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                @foreach(['active','inactive','draft'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-light w-100">Filter</button>
        </div>
    </form>
</div>

<div class="glass-card">
    <div class="table-responsive">
        <table class="table table-admin mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Questions</th>
                    <th>Sessions</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($campaigns as $campaign)
                    <tr>
                        <td>
                            <strong>{{ $campaign->name }}</strong>
                            <div class="small text-muted-admin">{{ $campaign->slug }}</div>
                        </td>
                        <td>{{ str_replace('_', ' ', $campaign->type) }}</td>
                        <td><span class="badge badge-status-{{ $campaign->status }}">{{ ucfirst($campaign->status) }}</span></td>
                        <td>{{ $campaign->questions_count }}</td>
                        <td>{{ $campaign->player_sessions_count }}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                @if($campaign->type === 'sundarikk_pottu')
                                    <a href="{{ route('admin.campaigns.pottu-images.index', $campaign) }}" class="btn btn-outline-light">Pottu</a>
                                @else
                                    <a href="{{ route('admin.campaigns.questions.index', $campaign) }}" class="btn btn-outline-light">Questions</a>
                                @endif
                                <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="btn btn-outline-light">Edit</a>
                                <form action="{{ route('admin.campaigns.toggle-status', $campaign) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-outline-warning">Toggle</button>
                                </form>
                                <form action="{{ route('admin.campaigns.clone', $campaign) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-outline-info">Clone</button>
                                </form>
                                <form action="{{ route('admin.campaigns.destroy', $campaign) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this campaign?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted-admin">No campaigns found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($campaigns->hasPages())
        <div class="p-3">{{ $campaigns->links() }}</div>
    @endif
</div>
@endsection
