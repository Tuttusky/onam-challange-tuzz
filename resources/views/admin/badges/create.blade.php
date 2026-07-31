@extends('admin.layouts.app')

@section('title', 'Create Badge')
@section('page-title', 'Create Badge')

@section('content')
<div class="glass-card p-4"><form method="POST" action="{{ route('admin.badges.store') }}">@csrf @include('admin.badges._form')<button class="btn btn-admin-primary mt-3">Create</button></form></div>
@endsection
