@php $seo = $seo ?? null; @endphp
<div class="row g-3">
    <div class="col-12"><label class="form-label">Meta Title</label><input name="meta_title" class="form-control" value="{{ old('meta_title', $seo?->meta_title) }}"></div>
    <div class="col-12"><label class="form-label">Meta Description</label><textarea name="meta_description" class="form-control" rows="2">{{ old('meta_description', $seo?->meta_description) }}</textarea></div>
    <div class="col-12"><label class="form-label">Meta Keywords</label><input name="meta_keywords" class="form-control" value="{{ old('meta_keywords', $seo?->meta_keywords) }}"></div>
    <div class="col-md-6"><label class="form-label">OG Title</label><input name="og_title" class="form-control" value="{{ old('og_title', $seo?->og_title) }}"></div>
    <div class="col-md-6"><label class="form-label">OG Image URL</label><input name="og_image" class="form-control" value="{{ old('og_image', $seo?->og_image) }}"></div>
    <div class="col-12"><label class="form-label">OG Description</label><textarea name="og_description" class="form-control" rows="2">{{ old('og_description', $seo?->og_description) }}</textarea></div>
    <div class="col-md-6"><label class="form-label">Canonical URL</label><input name="canonical_url" class="form-control" value="{{ old('canonical_url', $seo?->canonical_url) }}"></div>
    <div class="col-md-6"><label class="form-label">Robots</label><input name="robots" class="form-control" value="{{ old('robots', $seo?->robots) }}" placeholder="index, follow"></div>
    <div class="col-md-4"><label class="form-label">Google Verification</label><input name="google_verification" class="form-control" value="{{ old('google_verification', $seo?->google_verification) }}"></div>
    <div class="col-md-4"><label class="form-label">Bing Verification</label><input name="bing_verification" class="form-control" value="{{ old('bing_verification', $seo?->bing_verification) }}"></div>
    <div class="col-md-4"><label class="form-label">Facebook Verification</label><input name="facebook_verification" class="form-control" value="{{ old('facebook_verification', $seo?->facebook_verification) }}"></div>
</div>
