@extends('admin.layouts.app')

@section('title', 'Questions')
@section('page-title', $campaign->name.' — Questions')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('admin.campaigns.index') }}" class="text-muted-admin small">&larr; Back to Campaigns</a>
        <h2 class="page-title mb-0">{{ $campaign->name }} Questions</h2>
    </div>
    <a href="{{ route('admin.campaigns.questions.create', $campaign) }}" class="btn btn-admin-primary">Add Question</a>
</div>

<div class="glass-card">
    <form method="POST" action="{{ route('admin.campaigns.questions.reorder', $campaign) }}" id="reorderForm">
        @csrf
        <div class="table-responsive">
            <table class="table table-admin mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Points</th>
                        <th>Active</th>
                        <th>Options</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="sortableQuestions">
                    @forelse($questions as $question)
                        <tr>
                            <td>
                                <input type="hidden" name="order[]" value="{{ $question->id }}">
                                <span class="text-muted-admin">{{ $question->sort_order }}</span>
                            </td>
                            <td>{{ $question->title }}</td>
                            <td>{{ str_replace('_', ' ', $question->type) }}</td>
                            <td>{{ $question->points }}</td>
                            <td>{{ $question->is_active ? 'Yes' : 'No' }}</td>
                            <td>{{ $question->options->count() }}</td>
                            <td>
                                <a href="{{ route('admin.campaigns.questions.edit', [$campaign, $question]) }}" class="btn btn-sm btn-outline-light">Edit</a>
                                <form action="{{ route('admin.campaigns.questions.destroy', [$campaign, $question]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete question?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted-admin">No questions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($questions->count())
            <div class="p-3 border-top border-secondary">
                <button type="submit" class="btn btn-outline-warning btn-sm">Save Order</button>
            </div>
        @endif
    </form>
    @if($questions->hasPages())
        <div class="p-3">{{ $questions->links() }}</div>
    @endif
</div>
@endsection
