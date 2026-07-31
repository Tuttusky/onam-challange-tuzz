@php $banner = $banner ?? null; @endphp
<div class="row g-3">
    <div class="col-md-6"><label class="form-label">Title</label><input name="title" class="form-control" value="{{ old('title', $banner?->title) }}" required></div>
    <div class="col-md-6"><label class="form-label">Type</label><select name="type" class="form-select">@foreach(['header','footer','popup','festival','advertisement'] as $t)<option value="{{ $t }}" @selected(old('type', $banner?->type) === $t)>{{ ucfirst($t) }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Image URL</label><input name="image" class="form-control" value="{{ old('image', $banner?->image) }}"></div>
    <div class="col-md-6"><label class="form-label">Link</label><input name="link" class="form-control" value="{{ old('link', $banner?->link) }}"></div>
    <div class="col-12"><label class="form-label">Content</label><textarea name="content" class="form-control" rows="3">{{ old('content', $banner?->content) }}</textarea></div>
    <div class="col-md-3"><label class="form-label">Starts At</label><input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at', $banner?->starts_at?->format('Y-m-d\TH:i')) }}"></div>
    <div class="col-md-3"><label class="form-label">Ends At</label><input type="datetime-local" name="ends_at" class="form-control" value="{{ old('ends_at', $banner?->ends_at?->format('Y-m-d\TH:i')) }}"></div>
    <div class="col-md-2"><label class="form-label">Sort</label><input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $banner?->sort_order ?? 0) }}"></div>
    <div class="col-md-2 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $banner?->is_active ?? true))><label class="form-check-label">Active</label></div></div>
</div>
