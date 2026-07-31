@extends('admin.layouts.app')

@section('title', 'Edit CMS Page')
@section('page-title', 'Edit CMS Page')

@section('content')
<div class="glass-card p-4">
    <form method="POST" action="{{ route('admin.cms.update', $cmsPage) }}">@csrf @method('PUT')
        @include('admin.cms._form', ['page' => $cmsPage])
        <button type="submit" class="btn btn-admin-primary mt-3">Update Page</button>
    </form>
</div>
@endsection
