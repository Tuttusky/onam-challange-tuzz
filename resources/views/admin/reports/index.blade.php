@extends('admin.layouts.app')

@section('title', 'Reports')
@section('page-title', 'Reports')

@section('content')
<div class="glass-card p-3 mb-4">
    <form method="GET" class="row g-2">
        <div class="col-md-3">
            <select name="days" class="form-select">
                @foreach([7,14,30,60,90] as $d)
                    <option value="{{ $d }}" @selected($days == $d)>Last {{ $d }} days</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <select name="campaign_id" class="form-select">
                <option value="">All Campaigns</option>
                @foreach($campaigns as $c)
                    <option value="{{ $c->id }}" @selected($campaignId == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-outline-light w-100">Apply</button></div>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card"><div class="stat-value">{{ number_format($visitors) }}</div><div class="stat-label">Unique Visitors</div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="stat-value">{{ number_format($totalEvents) }}</div><div class="stat-label">Total Events</div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="stat-value">{{ number_format($completed) }}/{{ number_format($started) }}</div><div class="stat-label">Completed Sessions</div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="stat-value">{{ $completionRate }}%</div><div class="stat-label">Completion Rate</div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="glass-card p-4">
            <h5 class="text-white mb-3">Devices</h5>
            @forelse($devices as $row)
                <div class="d-flex justify-content-between mb-2"><span>{{ $row->device }}</span><span class="text-warning">{{ $row->total }}</span></div>
            @empty<p class="text-muted-admin">No data.</p>@endforelse
        </div>
    </div>
    <div class="col-lg-4">
        <div class="glass-card p-4">
            <h5 class="text-white mb-3">Browsers</h5>
            @forelse($browsers as $row)
                <div class="d-flex justify-content-between mb-2"><span>{{ $row->browser }}</span><span class="text-warning">{{ $row->total }}</span></div>
            @empty<p class="text-muted-admin">No data.</p>@endforelse
        </div>
    </div>
    <div class="col-lg-4">
        <div class="glass-card p-4">
            <h5 class="text-white mb-3">Countries</h5>
            @forelse($countries as $row)
                <div class="d-flex justify-content-between mb-2"><span>{{ $row->country }}</span><span class="text-warning">{{ $row->total }}</span></div>
            @empty<p class="text-muted-admin">No data.</p>@endforelse
        </div>
    </div>
</div>

<div class="glass-card p-4 mt-4">
    <h5 class="text-white mb-3">Daily Visitors</h5>
    <canvas id="visitorsChart" height="80"></canvas>
</div>
@endsection

@push('scripts')
<script>
new Chart(document.getElementById('visitorsChart'), {
    type: 'bar',
    data: {
        labels: @json($dailyVisitors->pluck('date')),
        datasets: [{ label: 'Visitors', data: @json($dailyVisitors->pluck('total')), backgroundColor: 'rgba(99, 102, 241, 0.6)' }]
    },
    options: { plugins: { legend: { labels: { color: '#fff' } } }, scales: { x: { ticks: { color: '#aaa' } }, y: { ticks: { color: '#aaa' } } } }
});
</script>
@endpush
