@extends('admin.layouts.app')

@section('title', 'Referrals')
@section('page-title', 'Referrals')

@section('content')
<div class="glass-card">
    <div class="table-responsive">
        <table class="table table-admin mb-0">
            <thead><tr><th>Referrer</th><th>Referred</th><th>Campaign</th><th>Reward</th><th>Date</th></tr></thead>
            <tbody>
                @forelse($referrals as $referral)
                    <tr>
                        <td><a href="{{ route('admin.players.show', $referral->referrer) }}" class="text-white">{{ $referral->referrer?->name }}</a></td>
                        <td><a href="{{ route('admin.players.show', $referral->referred) }}" class="text-white">{{ $referral->referred?->name }}</a></td>
                        <td>{{ $referral->campaign?->name ?? '—' }}</td>
                        <td>{{ $referral->reward_points }}</td>
                        <td>{{ $referral->created_at?->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted-admin">No referrals yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($referrals->hasPages())<div class="p-3">{{ $referrals->links() }}</div>@endif
</div>
@endsection
