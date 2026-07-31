@extends('admin.layouts.app')

@section('title', 'Edit Campaign')
@section('page-title', 'Edit Campaign')

@section('content')
<div class="glass-card p-4">
    <form method="POST" action="{{ route('admin.campaigns.update', $campaign) }}">
        @csrf @method('PUT')
        @include('admin.campaigns._form', ['campaign' => $campaign])
        <div class="mt-4">
            <button type="submit" class="btn btn-admin-primary">Update Campaign</button>
            <a href="{{ route('admin.campaigns.index') }}" class="btn btn-outline-light">Cancel</a>
        </div>
    </form>
</div>
@endsection
