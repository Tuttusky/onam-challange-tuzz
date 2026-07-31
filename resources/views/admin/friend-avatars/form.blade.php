@extends('admin.layouts.app')

@section('title', $avatar->exists ? 'Edit Avatar' : 'Add Avatar')
@section('page-title', $avatar->exists ? 'Edit Avatar' : 'Add Avatar')

@section('content')
<div class="glass-card p-4">
    <form method="POST" action="{{ $avatar->exists ? route('admin.friend-avatars.update', $avatar) : route('admin.friend-avatars.store') }}">
        @csrf
        @if($avatar->exists) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Name</label>
                <input name="name" class="form-control" value="{{ old('name', $avatar->name) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Slug</label>
                <input name="slug" class="form-control" value="{{ old('slug', $avatar->slug) }}" required>
            </div>
            <div class="col-md-8">
                <label class="form-label">Image Path</label>
                <input name="path" class="form-control" value="{{ old('path', $avatar->path) }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $avatar->sort_order ?? 0) }}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="active" @checked(old('is_active', $avatar->is_active ?? true))>
                    <label for="active" class="form-check-label">Active</label>
                </div>
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-admin-primary">Save</button>
            <a href="{{ route('admin.friend-avatars.index') }}" class="btn btn-glass">Cancel</a>
        </div>
    </form>
</div>
@endsection
