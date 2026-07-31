@php $question = $question ?? null; @endphp
<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label">Title</label>
        <input type="text" name="title" class="form-control" value="{{ old('title', $question?->title) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Type</label>
        <select name="type" class="form-select" required>
            @foreach(['yes_no','multiple_choice','emoji','text','image','video'] as $type)
                <option value="{{ $type }}" @selected(old('type', $question?->type) === $type)>{{ str_replace('_', ' ', $type) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="2">{{ old('description', $question?->description) }}</textarea>
    </div>
    <div class="col-md-4">
        <label class="form-label">Category</label>
        <select name="category_id" class="form-select">
            <option value="">None</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $question?->category_id) == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Points</label>
        <input type="number" name="points" class="form-control" value="{{ old('points', $question?->points ?? 1) }}" min="0">
    </div>
    <div class="col-md-2">
        <label class="form-label">Sort Order</label>
        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $question?->sort_order ?? 0) }}" min="0">
    </div>
    <div class="col-md-2">
        <label class="form-label">Difficulty</label>
        <input type="text" name="difficulty" class="form-control" value="{{ old('difficulty', $question?->difficulty ?? 'medium') }}">
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <div class="form-check">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="q_active" @checked(old('is_active', $question?->is_active ?? true))>
            <label class="form-check-label" for="q_active">Active</label>
        </div>
    </div>
</div>
