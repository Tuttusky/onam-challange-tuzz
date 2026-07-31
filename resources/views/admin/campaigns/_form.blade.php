@php $campaign = $campaign ?? null; @endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $campaign?->name) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Slug</label>
        <input type="text" name="slug" class="form-control" value="{{ old('slug', $campaign?->slug) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Type</label>
        <select name="type" class="form-select" required>
            @foreach(['dare_challenge','sundarikk_pottu','quiz','poll','survey'] as $type)
                <option value="{{ $type }}" @selected(old('type', $campaign?->type) === $type)>{{ str_replace('_', ' ', ucfirst($type)) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select" required>
            @foreach(['draft','active','inactive'] as $status)
                <option value="{{ $status }}" @selected(old('status', $campaign?->status ?? 'draft') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Theme</label>
        <select name="campaign_theme_id" class="form-select">
            <option value="">None</option>
            @foreach($themes as $theme)
                <option value="{{ $theme->id }}" @selected(old('campaign_theme_id', $campaign?->campaign_theme_id) == $theme->id)>{{ $theme->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3">{{ old('description', $campaign?->description) }}</textarea>
    </div>
    <div class="col-md-3">
        <label class="form-label">Starts At</label>
        <input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at', $campaign?->starts_at?->format('Y-m-d\TH:i')) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Ends At</label>
        <input type="datetime-local" name="ends_at" class="form-control" value="{{ old('ends_at', $campaign?->ends_at?->format('Y-m-d\TH:i')) }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">Max Questions</label>
        <input type="number" name="max_questions" class="form-control" value="{{ old('max_questions', $campaign?->max_questions ?? 10) }}" min="1">
    </div>
    <div class="col-md-2">
        <label class="form-label">Max Friends</label>
        <input type="number" name="max_friends" class="form-control" value="{{ old('max_friends', $campaign?->max_friends ?? 50) }}" min="1">
    </div>
    <div class="col-md-2">
        <label class="form-label">Sort Order</label>
        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $campaign?->sort_order ?? 0) }}" min="0">
    </div>
    <div class="col-12">
        <label class="form-label">Share Message</label>
        <textarea name="share_message" class="form-control" rows="2">{{ old('share_message', $campaign?->share_message) }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-label">Default Challenge Title Template</label>
        <input name="default_challenge_title" class="form-control" value="{{ old('default_challenge_title', $campaign?->default_challenge_title ?? 'Hey {friend_name}, Can You Beat Me?') }}" placeholder="Hey {friend_name}, Can You Beat Me?">
    </div>
    <div class="col-12">
        <div class="form-check">
            <input type="checkbox" name="is_featured" value="1" class="form-check-input" id="is_featured" @checked(old('is_featured', $campaign?->is_featured))>
            <label class="form-check-label" for="is_featured">Featured Campaign</label>
        </div>
    </div>
</div>
