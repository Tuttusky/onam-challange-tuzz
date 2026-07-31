@extends('admin.layouts.app')

@section('title', 'Edit Result Message')
@section('page-title', 'Edit Result Message')

@section('content')
<div class="glass-card p-4"><form method="POST" action="{{ route('admin.result-messages.update', $resultMessage) }}">@csrf @method('PUT') @include('admin.result-messages._form', ['message' => $resultMessage])<button class="btn btn-admin-primary mt-3">Update</button></form></div>
@endsection
