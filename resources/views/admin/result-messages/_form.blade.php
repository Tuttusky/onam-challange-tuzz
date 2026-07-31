@php $message = $message ?? null; @endphp
<div class="row g-3">
    <div class="col-md-6"><label class="form-label">Campaign</label><select name="campaign_id" class="form-select"><option value="">Global</option>@foreach($campaigns as $c)<option value="{{ $c->id }}" @selected(old('campaign_id', $message?->campaign_id) == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Min Match %</label><input type="number" name="min_match_percent" class="form-control" value="{{ old('min_match_percent', $message?->min_match_percent ?? 0) }}"></div>
    <div class="col-md-3"><label class="form-label">Max Match %</label><input type="number" name="max_match_percent" class="form-control" value="{{ old('max_match_percent', $message?->max_match_percent ?? 100) }}"></div>
    <div class="col-12"><label class="form-label">Message</label><textarea name="message" class="form-control" rows="4" required>{{ old('message', $message?->message) }}</textarea></div>
    <div class="col-12"><div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $message?->is_active ?? true))><label class="form-check-label">Active</label></div></div>
</div>
