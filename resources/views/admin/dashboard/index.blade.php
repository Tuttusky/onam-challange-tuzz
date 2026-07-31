@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-value">{{ number_format($stats['total_players']) }}</div>
            <div class="stat-label">Total Players</div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-value">{{ number_format($stats['today_players']) }}</div>
            <div class="stat-label">Today Players</div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-value">{{ number_format($stats['active_challenges']) }}</div>
            <div class="stat-label">Active Challenges</div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-value">{{ number_format($stats['completed_challenges']) }}</div>
            <div class="stat-label">Completed</div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-value">{{ number_format($stats['share_count']) }}</div>
            <div class="stat-label">Share Count</div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-value">{{ number_format($stats['active_campaigns']) }}</div>
            <div class="stat-label">Active Campaigns</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="glass-card p-4">
            <h5 class="text-white mb-3">Activity (Last 7 Days)</h5>
            <canvas id="dashboardChart" height="100"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="glass-card p-4 h-100">
            <h5 class="text-white mb-3">Top Players</h5>
            <div class="list-group list-group-flush">
                @forelse($topPlayers as $player)
                    <a href="{{ route('admin.players.show', $player) }}" class="list-group-item bg-transparent text-white border-secondary d-flex justify-content-between">
                        <span>{{ $player->name }}</span>
                        <span class="text-warning">{{ $player->share_count }} shares</span>
                    </a>
                @empty
                    <p class="text-muted-admin mb-0">No players yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="glass-card">
    <div class="card-header py-3 px-4">
        <h5 class="mb-0">Recent Challenge Results</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-admin mb-0">
            <thead>
                <tr>
                    <th>Campaign</th>
                    <th>Match %</th>
                    <th>Winner</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentResults as $result)
                    <tr>
                        <td>{{ $result->challengeLink?->campaign?->name ?? '—' }}</td>
                        <td>{{ $result->match_percent }}%</td>
                        <td>{{ $result->winner?->name ?? '—' }}</td>
                        <td>{{ $result->created_at?->format('M d, Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted-admin">No results yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    new Chart(document.getElementById('dashboardChart'), {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [
                {
                    label: 'New Players',
                    data: @json($playersChart),
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.15)',
                    tension: 0.35,
                    fill: true
                },
                {
                    label: 'Completions',
                    data: @json($completionsChart),
                    borderColor: '#818cf8',
                    backgroundColor: 'rgba(129, 140, 248, 0.1)',
                    tension: 0.35,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { labels: { color: '#fff' } } },
            scales: {
                x: { ticks: { color: '#aaa' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                y: { ticks: { color: '#aaa' }, grid: { color: 'rgba(255,255,255,0.05)' } }
            }
        }
    });
</script>
@endpush
