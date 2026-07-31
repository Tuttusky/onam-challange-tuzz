@php $page = $page ?? null; @endphp
<div class="row g-3">
    <div class="col-md-8"><label class="form-label">Title</label><input name="title" class="form-control" value="{{ old('title', $page?->title) }}" required></div>
    <div class="col-md-4"><label class="form-label">Slug</label><input name="slug" class="form-control" value="{{ old('slug', $page?->slug) }}"></div>
    <div class="col-12"><label class="form-label">Content</label><textarea name="content" class="form-control" rows="12" required>{{ old('content', $page?->content) }}</textarea></div>
    <div class="col-md-3"><label class="form-label">Sort Order</label><input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $page?->sort_order ?? 0) }}"></div>
    <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="is_published" value="1" class="form-check-input" @checked(old('is_published', $page?->is_published))><label class="form-check-label">Published</label></div></div>
</div>
