@php $badge = $badge ?? null; @endphp
<div class="row g-3">
    <div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $badge?->name) }}" required></div>
    <div class="col-md-6"><label class="form-label">Campaign</label><select name="campaign_id" class="form-select"><option value="">Global</option>@foreach($campaigns as $c)<option value="{{ $c->id }}" @selected(old('campaign_id', $badge?->campaign_id) == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Image URL</label><input name="image" class="form-control" value="{{ old('image', $badge?->image) }}"></div>
    <div class="col-md-3"><label class="form-label">Min Match %</label><input type="number" name="min_match_percent" class="form-control" value="{{ old('min_match_percent', $badge?->min_match_percent ?? 0) }}" min="0" max="100"></div>
    <div class="col-md-3"><label class="form-label">Max Match %</label><input type="number" name="max_match_percent" class="form-control" value="{{ old('max_match_percent', $badge?->max_match_percent ?? 100) }}" min="0" max="100"></div>
    <div class="col-md-3"><label class="form-label">Sort Order</label><input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $badge?->sort_order ?? 0) }}"></div>
    <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $badge?->is_active ?? true))><label class="form-check-label">Active</label></div></div>
</div>
