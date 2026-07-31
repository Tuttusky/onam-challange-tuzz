@extends('admin.layouts.app')

@section('title', 'Edit Question')
@section('page-title', 'Edit Question')

@section('content')
<div class="row g-4">
    <div class="col-lg-7">
        <div class="glass-card p-4">
            <p class="text-muted-admin">Campaign: <strong class="text-white">{{ $campaign->name }}</strong></p>
            <form method="POST" action="{{ route('admin.campaigns.questions.update', [$campaign, $question]) }}">
                @csrf @method('PUT')
                @include('admin.questions._form', ['question' => $question])
                <div class="mt-4">
                    <button type="submit" class="btn btn-admin-primary">Update Question</button>
                    <a href="{{ route('admin.campaigns.questions.index', $campaign) }}" class="btn btn-outline-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="glass-card p-4">
            <h5 class="text-white mb-3">Options</h5>
            @foreach($question->options as $option)
                <div class="border border-secondary rounded p-3 mb-3">
                    <form method="POST" action="{{ route('admin.questions.options.update', [$question, $option]) }}">
                        @csrf @method('PUT')
                        <div class="row g-2">
                            <div class="col-6"><input name="label" class="form-control form-control-sm" value="{{ $option->label }}" required></div>
                            <div class="col-6"><input name="value" class="form-control form-control-sm" value="{{ $option->value }}" required></div>
                            <div class="col-4"><input name="points" type="number" class="form-control form-control-sm" value="{{ $option->points }}"></div>
                            <div class="col-8"><button class="btn btn-sm btn-outline-light">Save Option</button></div>
                        </div>
                    </form>
                    <form action="{{ route('admin.questions.options.destroy', [$question, $option]) }}" method="POST" class="mt-2" onsubmit="return confirm('Delete option?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Delete Option</button>
                    </form>
                </div>
            @endforeach
            <hr class="border-secondary">
            <form method="POST" action="{{ route('admin.questions.options.store', $question) }}">
                @csrf
                <div class="row g-2">
                    <div class="col-6"><input name="label" class="form-control form-control-sm" placeholder="Label" required></div>
                    <div class="col-6"><input name="value" class="form-control form-control-sm" placeholder="Value" required></div>
                    <div class="col-12"><button class="btn btn-sm btn-admin-primary">Add Option</button></div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
