@extends('admin.layouts.app')

@section('title', 'Challenge Results')
@section('page-title', 'Challenge Results')

@section('content')
<div class="glass-card">
    <div class="table-responsive">
        <table class="table table-admin mb-0">
            <thead><tr><th>Challenge</th><th>Image</th><th>Match</th><th>Winner</th><th>Badge</th><th>Date</th><th></th></tr></thead>
            <tbody>
                @forelse($results as $result)
                    <tr>
                        <td>
                            <div>#{{ $result->challenge_link_id }}</div>
                            <code class="small">{{ $result->challengeLink?->code ?? '—' }}</code>
                        </td>
                        <td>
                            @if($result->challengeLink?->pottuImage)
                                <img src="{{ $result->challengeLink->pottuImage->url }}" alt="" style="width:40px;height:52px;object-fit:cover;border-radius:6px;">
                            @else
                                <span class="text-muted-admin">—</span>
                            @endif
                        </td>
                        <td>{{ $result->match_count }}/{{ $result->total_questions }} ({{ $result->match_percent }}%)</td>
                        <td>{{ $result->winner?->name ?? '—' }}</td>
                        <td>{{ $result->badge?->name ?? '—' }}</td>
                        <td>{{ $result->created_at?->format('M d, Y H:i') }}</td>
                        <td><a href="{{ route('admin.challenge-results.show', $result) }}" class="btn btn-sm btn-outline-light">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted-admin">No results yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($results->hasPages())<div class="p-3">{{ $results->links() }}</div>@endif
</div>
@endsection
