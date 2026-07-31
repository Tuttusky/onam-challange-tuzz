@extends('admin.layouts.app')

@section('title', 'Pottu Settings — ' . $campaign->name)
@section('page-title', 'Pottu Settings')

@section('content')
<div class="mb-3">
    <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="btn btn-outline-light btn-sm">Back to Campaign</a>
</div>
@include('admin.pottu._settings-form', [
    'action' => route('admin.campaigns.pottu-settings.update', $campaign),
    'settings' => $settings,
])
@endsection
