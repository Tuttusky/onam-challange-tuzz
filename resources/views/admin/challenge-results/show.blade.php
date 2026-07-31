@extends('admin.layouts.app')

@section('title', 'Challenge Result')
@section('page-title', 'Challenge Result Details')

@section('content')
<div class="mb-3"><a href="{{ route('admin.challenge-results.index') }}" class="text-muted-admin">&larr; Back</a></div>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="glass-card p-4">
            <dl class="row">
                <dt class="col-md-3 text-muted-admin">Result UUID</dt><dd class="col-md-9"><code>{{ $challengeResult->uuid }}</code></dd>
                <dt class="col-md-3 text-muted-admin">Challenge ID</dt><dd class="col-md-9">#{{ $challengeResult->challenge_link_id }}</dd>
                <dt class="col-md-3 text-muted-admin">Challenge Code</dt>
                <dd class="col-md-9">
                    @if($challengeResult->challengeLink)
                        <a href="{{ route('admin.challenge-links.show', $challengeResult->challengeLink) }}" class="text-white">
                            <code>{{ $challengeResult->challengeLink->code }}</code>
                        </a>
                    @else
                        —
                    @endif
                </dd>
                <dt class="col-md-3 text-muted-admin">Campaign</dt><dd class="col-md-9">{{ $challengeResult->challengeLink?->campaign?->name }}</dd>
                <dt class="col-md-3 text-muted-admin">Creator</dt><dd class="col-md-9">{{ $challengeResult->creatorSession?->player?->name ?? '—' }}</dd>
                <dt class="col-md-3 text-muted-admin">Challenger</dt><dd class="col-md-9">{{ $challengeResult->challengerSession?->player?->name ?? '—' }}</dd>
                <dt class="col-md-3 text-muted-admin">Match</dt><dd class="col-md-9">{{ $challengeResult->match_count }}/{{ $challengeResult->total_questions }} ({{ $challengeResult->match_percent }}%)</dd>
                <dt class="col-md-3 text-muted-admin">Winner</dt><dd class="col-md-9"><strong>{{ $challengeResult->winner?->name ?? '—' }}</strong></dd>
                <dt class="col-md-3 text-muted-admin">Badge</dt><dd class="col-md-9">{{ $challengeResult->badge?->name ?? '—' }}</dd>
                <dt class="col-md-3 text-muted-admin">Message</dt><dd class="col-md-9">{{ $challengeResult->resultMessage?->message ?? '—' }}</dd>
                <dt class="col-md-3 text-muted-admin">Created</dt><dd class="col-md-9">{{ $challengeResult->created_at?->format('M d, Y H:i:s') }}</dd>
            </dl>
        </div>
    </div>

    @if($challengeResult->challengeLink?->pottuImage)
        <div class="col-lg-4">
            <div class="glass-card p-4">
                <h5 class="text-white mb-3">Challenge Image</h5>
                <img
                    src="{{ $challengeResult->challengeLink->pottuImage->url }}"
                    alt="{{ $challengeResult->challengeLink->pottuImage->title }}"
                    class="img-fluid rounded mb-2"
                    style="max-height: 360px; object-fit: cover; width: 100%;"
                >
                <p class="small mb-1">{{ $challengeResult->challengeLink->pottuImage->title }}</p>
                @if(str_contains($challengeResult->challengeLink->pottuImage->path, 'pottu-custom-images'))
                    <span class="badge bg-warning text-dark">Custom Upload</span>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
