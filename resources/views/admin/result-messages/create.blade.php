@extends('admin.layouts.app')

@section('title', 'Create Result Message')
@section('page-title', 'Create Result Message')

@section('content')
<div class="glass-card p-4"><form method="POST" action="{{ route('admin.result-messages.store') }}">@csrf @include('admin.result-messages._form')<button class="btn btn-admin-primary mt-3">Create</button></form></div>
@endsection
