@extends('admin.layouts.app')

@section('title', 'Pottu Settings')
@section('page-title', 'Pottu Settings')

@section('content')
@include('admin.pottu._settings-form', [
    'action' => route('admin.settings.pottu'),
    'settings' => $settings,
])
@endsection
