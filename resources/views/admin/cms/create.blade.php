@extends('admin.layouts.app')

@section('title', 'Create CMS Page')
@section('page-title', 'Create CMS Page')

@section('content')
<div class="glass-card p-4">
    <form method="POST" action="{{ route('admin.cms.store') }}">@csrf
        @include('admin.cms._form')
        <button type="submit" class="btn btn-admin-primary mt-3">Create Page</button>
    </form>
</div>
@endsection
