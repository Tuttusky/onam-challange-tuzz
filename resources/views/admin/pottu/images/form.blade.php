@extends('admin.layouts.app')

@section('title', ($image->exists ? 'Edit' : 'Add') . ' Girl Image')
@section('page-title', ($image->exists ? 'Edit' : 'Add') . ' Girl Image')

@section('content')
<div class="glass-card p-4">
    <form method="POST" action="{{ $image->exists ? route('admin.campaigns.pottu-images.update', [$campaign, $image]) : route('admin.campaigns.pottu-images.store', $campaign) }}">
        @csrf
        @if($image->exists) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Title</label><input name="title" class="form-control" value="{{ old('title', $image->title) }}"></div>
            <div class="col-md-6"><label class="form-label">Image Path / URL</label><input name="path" class="form-control" value="{{ old('path', $image->path) }}" required></div>
            <div class="col-md-3"><label class="form-label">Width</label><input type="number" name="width" class="form-control" value="{{ old('width', $image->width) }}"></div>
            <div class="col-md-3"><label class="form-label">Height</label><input type="number" name="height" class="form-control" value="{{ old('height', $image->height) }}"></div>
            <div class="col-md-3"><label class="form-label">Sort Order</label><input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $image->sort_order ?? 0) }}"></div>
            <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="active" @checked(old('is_active', $image->is_active ?? true))><label for="active" class="form-check-label">Active</label></div></div>
        </div>
        <div class="mt-4">
            <button class="btn btn-admin-primary">Save</button>
            <a href="{{ route('admin.campaigns.pottu-images.index', $campaign) }}" class="btn btn-outline-light">Cancel</a>
        </div>
    </form>
</div>
@endsection
