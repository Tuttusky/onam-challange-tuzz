@extends('admin.layouts.app')

@section('title', 'Players')
@section('page-title', 'Players')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="page-title mb-0">Players</h2>
    <a href="{{ route('admin.players.export', request()->query()) }}" class="btn btn-outline-light">Export CSV</a>
</div>

<div class="glass-card p-3 mb-4">
    <form method="GET" class="row g-2">
        <div class="col-md-4"><input type="text" name="search" class="form-control" placeholder="Search name or code" value="{{ request('search') }}"></div>
        <div class="col-md-3">
            <select name="country" class="form-select">
                <option value="">All Countries</option>
                @foreach($countries as $country)
                    <option value="{{ $country }}" @selected(request('country') === $country)>{{ $country }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-outline-light w-100">Filter</button></div>
    </form>
</div>

<div class="glass-card">
    <div class="table-responsive">
        <table class="table table-admin mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Referral Code</th>
                    <th>Country</th>
                    <th>Shares</th>
                    <th>Sessions</th>
                    <th>Joined</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($players as $player)
                    <tr>
                        <td>{{ $player->name }}</td>
                        <td><code>{{ $player->referral_code }}</code></td>
                        <td>{{ $player->country ?? '—' }}</td>
                        <td>{{ $player->share_count }}</td>
                        <td>{{ $player->sessions_count }}</td>
                        <td>{{ $player->created_at?->format('M d, Y') }}</td>
                        <td><a href="{{ route('admin.players.show', $player) }}" class="btn btn-sm btn-outline-light">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted-admin">No players found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($players->hasPages())<div class="p-3">{{ $players->links() }}</div>@endif
</div>
@endsection
