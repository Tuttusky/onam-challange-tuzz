@extends('admin.layouts.app')

@section('title', 'Create Campaign')
@section('page-title', 'Create Campaign')

@section('content')
<div class="glass-card p-4">
    <form method="POST" action="{{ route('admin.campaigns.store') }}">
        @csrf
        @include('admin.campaigns._form')
        <div class="mt-4">
            <button type="submit" class="btn btn-admin-primary">Create Campaign</button>
            <a href="{{ route('admin.campaigns.index') }}" class="btn btn-outline-light">Cancel</a>
        </div>
    </form>
</div>
@endsection
