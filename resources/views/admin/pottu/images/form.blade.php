@extends('admin.layouts.app')

@section('title', ($image->exists ? 'Edit' : 'Add') . ' Girl Image')
@section('page-title', ($image->exists ? 'Edit' : 'Add') . ' Girl Image')

@section('content')
<div class="glass-card p-4">
    <form method="POST" action="{{ $image->exists ? route('admin.campaigns.pottu-images.update', [$campaign, $image]) : route('admin.campaigns.pottu-images.store', $campaign) }}" enctype="multipart/form-data">
        @csrf
        @if($image->exists) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Title</label>
                <input name="title" class="form-control" value="{{ old('title', $image->title ?? 'Onam Girl') }}" placeholder="e.g. Onam Girl 1">
            </div>

            <div class="col-md-6">
                <label class="form-label">Upload Image File (JPG, PNG, WEBP)</label>
                <input type="file" name="image_file" class="form-control" accept="image/*">
                <small class="text-muted-admin">Upload a new photo from your device</small>
            </div>

            <div class="col-md-12">
                <label class="form-label">Or Image URL / Path</label>
                <input name="path" class="form-control" value="{{ old('path', $image->path) }}" placeholder="https://... or /storage/pottu-custom-images/...">
            </div>

            @if($image->exists && $image->url)
            <div class="col-12">
                <label class="form-label d-block">Current Image Preview</label>
                <img src="{{ $image->url }}" alt="" style="max-height: 180px; border-radius: 12px; border: 2px solid rgba(255,255,255,0.2);">
            </div>
            @endif

            <div class="col-md-3"><label class="form-label">Width (px)</label><input type="number" name="width" class="form-control" value="{{ old('width', $image->width ?? 600) }}"></div>
            <div class="col-md-3"><label class="form-label">Height (px)</label><input type="number" name="height" class="form-control" value="{{ old('height', $image->height ?? 900) }}"></div>
            <div class="col-md-3"><label class="form-label">Sort Order</label><input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $image->sort_order ?? 0) }}"></div>
            <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="active" @checked(old('is_active', $image->is_active ?? true))><label for="active" class="form-check-label">Active</label></div></div>
        </div>
        <div class="mt-4 d-flex align-items-center justify-content-between">
            <div>
                <button type="submit" class="btn btn-admin-primary">Save Image</button>
                <a href="{{ route('admin.campaigns.pottu-images.index', $campaign) }}" class="btn btn-outline-light ms-2">Cancel</a>
            </div>
            @if($image->exists)
            <form action="{{ route('admin.campaigns.pottu-images.destroy', [$campaign, $image]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this photo permanently?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Delete Photo</button>
            </form>
            @endif
        </div>
    </form>
</div>
@endsection
