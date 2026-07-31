@extends('admin.layouts.app')

@section('title', 'Analytics Settings')
@section('page-title', 'Analytics Settings')

@section('content')
<div class="glass-card p-4">
    <form method="POST" action="{{ route('admin.settings.analytics') }}">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-12"><div class="form-check"><input type="checkbox" name="enabled" value="1" class="form-check-input" id="enabled" @checked(old('enabled', ($settings['enabled'] ?? '1') === '1'))><label for="enabled" class="form-check-label">Enable Analytics</label></div></div>
            <div class="col-md-6"><label class="form-label">Google Analytics ID</label><input name="google_analytics_id" class="form-control" value="{{ old('google_analytics_id', $settings['google_analytics_id'] ?? '') }}"></div>
            <div class="col-md-6"><label class="form-label">Google Tag Manager ID</label><input name="google_tag_manager_id" class="form-control" value="{{ old('google_tag_manager_id', $settings['google_tag_manager_id'] ?? '') }}"></div>
            <div class="col-md-6"><label class="form-label">Facebook Pixel ID</label><input name="facebook_pixel_id" class="form-control" value="{{ old('facebook_pixel_id', $settings['facebook_pixel_id'] ?? '') }}"></div>
            <div class="col-md-6"><label class="form-label">Hotjar ID</label><input name="hotjar_id" class="form-control" value="{{ old('hotjar_id', $settings['hotjar_id'] ?? '') }}"></div>
            <div class="col-12"><label class="form-label">Custom Head Scripts</label><textarea name="custom_head_scripts" class="form-control" rows="4">{{ old('custom_head_scripts', $settings['custom_head_scripts'] ?? '') }}</textarea></div>
            <div class="col-12"><label class="form-label">Custom Body Scripts</label><textarea name="custom_body_scripts" class="form-control" rows="4">{{ old('custom_body_scripts', $settings['custom_body_scripts'] ?? '') }}</textarea></div>
        </div>
        <button type="submit" class="btn btn-admin-primary mt-4">Save Analytics Settings</button>
    </form>
</div>
@endsection
