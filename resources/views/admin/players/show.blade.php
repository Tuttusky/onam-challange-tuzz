@extends('admin.layouts.app')

@section('title', $player->name)
@section('page-title', 'Player Details')

@section('content')
<div class="mb-3"><a href="{{ route('admin.players.index') }}" class="text-muted-admin">&larr; Back to Players</a></div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="glass-card p-4">
            <h4 class="text-white">{{ $player->name }}</h4>
            <dl class="row small mb-0">
                <dt class="col-5 text-muted-admin">UUID</dt><dd class="col-7"><code>{{ $player->uuid }}</code></dd>
                <dt class="col-5 text-muted-admin">Referral</dt><dd class="col-7">{{ $player->referral_code }}</dd>
                <dt class="col-5 text-muted-admin">Country</dt><dd class="col-7">{{ $player->country ?? '—' }}</dd>
                <dt class="col-5 text-muted-admin">Device</dt><dd class="col-7">{{ $player->device ?? '—' }}</dd>
                <dt class="col-5 text-muted-admin">Browser</dt><dd class="col-7">{{ $player->browser ?? '—' }}</dd>
                <dt class="col-5 text-muted-admin">Shares</dt><dd class="col-7">{{ $player->share_count }}</dd>
                <dt class="col-5 text-muted-admin">Referred By</dt><dd class="col-7">{{ $player->referrer?->name ?? '—' }}</dd>
                <dt class="col-5 text-muted-admin">Joined</dt><dd class="col-7">{{ $player->created_at?->format('M d, Y H:i') }}</dd>
            </dl>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="glass-card mb-4">
            <div class="card-header py-3 px-4"><h5 class="mb-0">Sessions</h5></div>
            <div class="table-responsive">
                <table class="table table-admin mb-0">
                    <thead><tr><th>Campaign</th><th>Role</th><th>Status</th><th>Started</th></tr></thead>
                    <tbody>
                        @forelse($player->sessions as $session)
                            <tr>
                                <td>{{ $session->campaign?->name ?? '—' }}</td>
                                <td>{{ ucfirst($session->role) }}</td>
                                <td>{{ ucfirst($session->status) }}</td>
                                <td>{{ $session->started_at?->format('M d, Y') ?? $session->created_at?->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted-admin">No sessions.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="glass-card">
            <div class="card-header py-3 px-4"><h5 class="mb-0">Referrals ({{ $player->referrals->count() }})</h5></div>
            <ul class="list-group list-group-flush">
                @forelse($player->referrals as $referral)
                    <li class="list-group-item bg-transparent text-white border-secondary">{{ $referral->name }} — {{ $referral->created_at?->format('M d, Y') }}</li>
                @empty
                    <li class="list-group-item bg-transparent text-muted-admin border-secondary">No referrals.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
