@extends('admin.layouts.app')

@section('title', 'Create Banner')
@section('page-title', 'Create Banner')

@section('content')
<div class="glass-card p-4"><form method="POST" action="{{ route('admin.banners.store') }}">@csrf @include('admin.banners._form')<button class="btn btn-admin-primary mt-3">Create</button></form></div>
@endsection
