@extends('admin.layouts.app')

@section('title', ($style->exists ? 'Edit' : 'Add') . ' Pottu Style')
@section('page-title', ($style->exists ? 'Edit' : 'Add') . ' Pottu Style')

@section('content')
<div class="glass-card p-4">
    <form method="POST" action="{{ $style->exists ? route('admin.campaigns.pottu-styles.update', [$campaign, $style]) : route('admin.campaigns.pottu-styles.store', $campaign) }}">
        @csrf
        @if($style->exists) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $style->name) }}" required></div>
            <div class="col-md-4"><label class="form-label">Path / URL</label><input name="path" class="form-control" value="{{ old('path', $style->path) }}" required></div>
            <div class="col-md-4">
                <label class="form-label">Type</label>
                <select name="type" class="form-select">
                    @foreach(['image','lottie'] as $type)
                        <option value="{{ $type }}" @selected(old('type', $style->type ?? 'image') === $type)>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3"><label class="form-label">Default Size</label><input type="number" name="default_size" class="form-control" value="{{ old('default_size', $style->default_size ?? 48) }}"></div>
            <div class="col-md-3"><label class="form-label">Sort Order</label><input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $style->sort_order ?? 0) }}"></div>
            <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="active" @checked(old('is_active', $style->is_active ?? true))><label for="active" class="form-check-label">Active</label></div></div>
        </div>
        <div class="mt-4">
            <button class="btn btn-admin-primary">Save</button>
            <a href="{{ route('admin.campaigns.pottu-styles.index', $campaign) }}" class="btn btn-outline-light">Cancel</a>
        </div>
    </form>
</div>
@endsection
