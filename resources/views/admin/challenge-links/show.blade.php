@extends('admin.layouts.app')

@section('title', 'Challenge Link '.$challengeLink->code)
@section('page-title', 'Challenge Link Details')

@section('content')
<div class="mb-3"><a href="{{ route('admin.challenge-links.index') }}" class="text-muted-admin">&larr; Back</a></div>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="glass-card p-4">
            <h4 class="text-white"><code>{{ $challengeLink->code }}</code></h4>
            <dl class="row small mb-0">
                <dt class="col-5 text-muted-admin">Challenge ID</dt><dd class="col-7">#{{ $challengeLink->id }}</dd>
                <dt class="col-5 text-muted-admin">Campaign</dt><dd class="col-7">{{ $challengeLink->campaign?->name }}</dd>
                <dt class="col-5 text-muted-admin">Creator</dt><dd class="col-7">{{ $challengeLink->creatorSession?->player?->name ?? '—' }}</dd>
                <dt class="col-5 text-muted-admin">Friend</dt><dd class="col-7">{{ $challengeLink->friend_name ?? '—' }}</dd>
                <dt class="col-5 text-muted-admin">Shares</dt><dd class="col-7">{{ $challengeLink->share_count }}</dd>
                <dt class="col-5 text-muted-admin">Expires</dt><dd class="col-7">{{ $challengeLink->expires_at?->format('M d, Y H:i') ?? 'Never' }}</dd>
            </dl>
        </div>

        @if($challengeLink->pottuImage)
            <div class="glass-card p-4 mt-4">
                <h5 class="text-white mb-3">Pottu Image</h5>
                <img
                    src="{{ $challengeLink->pottuImage->url }}"
                    alt="{{ $challengeLink->pottuImage->title }}"
                    class="img-fluid rounded mb-2"
                    style="max-height: 320px; object-fit: cover; width: 100%;"
                >
                <p class="small mb-1">{{ $challengeLink->pottuImage->title }}</p>
                @if(str_contains($challengeLink->pottuImage->path, 'pottu-custom-images'))
                    <span class="badge bg-warning text-dark">Custom Upload</span>
                @endif
            </div>
        @endif
    </div>
    <div class="col-lg-8">
        <div class="glass-card">
            <div class="card-header py-3 px-4"><h5 class="mb-0">Results ({{ $challengeLink->results->count() }})</h5></div>
            <div class="table-responsive">
                <table class="table table-admin mb-0">
                    <thead><tr><th>Challenger</th><th>Match</th><th>Winner</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                        @forelse($challengeLink->results as $result)
                            <tr>
                                <td>{{ $result->challengerSession?->player?->name ?? '—' }}</td>
                                <td>{{ $result->match_percent }}%</td>
                                <td>{{ $result->winner?->name ?? '—' }}</td>
                                <td>{{ $result->created_at?->format('M d, Y') }}</td>
                                <td>
                                    <a href="{{ route('admin.challenge-results.show', $result) }}" class="btn btn-sm btn-outline-light">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted-admin">No results yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
