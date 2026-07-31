@extends('admin.layouts.app')

@section('title', 'Leaderboards')
@section('page-title', 'Leaderboards')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="glass-card p-3">
            <form method="GET" class="row g-2">
                <div class="col-md-3"><select name="period" class="form-select">@foreach(['daily','weekly','monthly','overall'] as $p)<option value="{{ $p }}" @selected($period === $p)>{{ ucfirst($p) }}</option>@endforeach</select></div>
                <div class="col-md-4"><select name="metric" class="form-select">@foreach(['most_shared','most_invites','highest_match','most_created','most_won','highest_accuracy','longest_chain'] as $m)<option value="{{ $m }}" @selected($metric === $m)>{{ str_replace('_',' ', ucfirst($m)) }}</option>@endforeach</select></div>
                <div class="col-md-3"><select name="campaign_id" class="form-select"><option value="">All Campaigns</option>@foreach($campaigns as $c)<option value="{{ $c->id }}" @selected($campaign?->id == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><button class="btn btn-outline-light w-100">Filter</button></div>
            </form>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="glass-card p-3">
            <form method="POST" action="{{ route('admin.leaderboards.rebuild') }}">@csrf
                <input type="hidden" name="period" value="{{ $period }}">
                <input type="hidden" name="metric" value="{{ $metric }}">
                @if($campaign)<input type="hidden" name="campaign_id" value="{{ $campaign->id }}">@endif
                <button class="btn btn-admin-primary w-100">Rebuild Leaderboard</button>
            </form>
            @if($lastSnapshot)<p class="small text-muted-admin mt-2 mb-0">Last snapshot: {{ \Carbon\Carbon::parse($lastSnapshot)->format('M d, Y H:i') }}</p>@endif
        </div>
    </div>
</div>

<div class="glass-card">
    <div class="table-responsive">
        <table class="table table-admin mb-0">
            <thead><tr><th>Rank</th><th>Player</th><th>Score</th><th>Date</th></tr></thead>
            <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td>#{{ $entry->rank }}</td>
                        <td><a href="{{ route('admin.players.show', $entry->player) }}" class="text-white">{{ $entry->player?->name }}</a></td>
                        <td>{{ number_format($entry->score) }}</td>
                        <td>{{ $entry->snapshot_date?->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted-admin">No leaderboard data. Run rebuild to generate.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
