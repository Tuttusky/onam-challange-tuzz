@extends('admin.layouts.app')

@section('title', 'Edit Banner')
@section('page-title', 'Edit Banner')

@section('content')
<div class="glass-card p-4"><form method="POST" action="{{ route('admin.banners.update', $banner) }}">@csrf @method('PUT') @include('admin.banners._form', ['banner' => $banner])<button class="btn btn-admin-primary mt-3">Update</button></form></div>
@endsection
