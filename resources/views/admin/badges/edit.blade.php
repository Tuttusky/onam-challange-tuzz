@extends('admin.layouts.app')

@section('title', 'Edit Badge')
@section('page-title', 'Edit Badge')

@section('content')
<div class="glass-card p-4"><form method="POST" action="{{ route('admin.badges.update', $badge) }}">@csrf @method('PUT') @include('admin.badges._form', ['badge' => $badge])<button class="btn btn-admin-primary mt-3">Update</button></form></div>
@endsection
