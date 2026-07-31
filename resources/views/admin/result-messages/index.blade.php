@extends('admin.layouts.app')

@section('title', 'Result Messages')
@section('page-title', 'Result Messages')

@section('content')
<div class="d-flex justify-content-between mb-4">
    <h2 class="page-title mb-0">Result Messages</h2>
    <a href="{{ route('admin.result-messages.create') }}" class="btn btn-admin-primary">New Message</a>
</div>
<div class="glass-card">
    <div class="table-responsive">
        <table class="table table-admin mb-0">
            <thead><tr><th>Message</th><th>Campaign</th><th>Range</th><th>Active</th><th></th></tr></thead>
            <tbody>
                @forelse($messages as $message)
                    <tr>
                        <td>{{ \Illuminate\Support\Str::limit($message->message, 60) }}</td>
                        <td>{{ $message->campaign?->name ?? 'Global' }}</td>
                        <td>{{ $message->min_match_percent }}% – {{ $message->max_match_percent }}%</td>
                        <td>{{ $message->is_active ? 'Yes' : 'No' }}</td>
                        <td>
                            <a href="{{ route('admin.result-messages.edit', $message) }}" class="btn btn-sm btn-outline-light">Edit</a>
                            <form action="{{ route('admin.result-messages.destroy', $message) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted-admin">No messages yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($messages->hasPages())<div class="p-3">{{ $messages->links() }}</div>@endif
</div>
@endsection
