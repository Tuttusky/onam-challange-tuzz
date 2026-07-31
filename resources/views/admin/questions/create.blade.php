@extends('admin.layouts.app')

@section('title', 'Create Question')
@section('page-title', 'Create Question')

@section('content')
<div class="glass-card p-4">
    <p class="text-muted-admin">Campaign: <strong class="text-white">{{ $campaign->name }}</strong></p>
    <form method="POST" action="{{ route('admin.campaigns.questions.store', $campaign) }}">
        @csrf
        @include('admin.questions._form')
        <div class="mt-4">
            <button type="submit" class="btn btn-admin-primary">Create Question</button>
            <a href="{{ route('admin.campaigns.questions.index', $campaign) }}" class="btn btn-outline-light">Cancel</a>
        </div>
    </form>
</div>
@endsection
