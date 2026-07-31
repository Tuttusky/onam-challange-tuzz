@extends('admin.layouts.app')

@section('title', 'Friend Challenge Settings')
@section('page-title', 'Friend Challenge Settings')

@section('content')
<div class="glass-card p-4">
    <form method="POST" action="{{ route('admin.settings.friend-challenge') }}">
        @csrf @method('PUT')

        <h5 class="text-white mb-3">Personalization</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="form-check">
                    <input type="checkbox" name="enable_photo_upload" value="1" class="form-check-input" id="photo" @checked(old('enable_photo_upload', $settings['enable_photo_upload'] ?? true))>
                    <label for="photo" class="form-check-label">Enable Photo Upload</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check">
                    <input type="checkbox" name="enable_avatar_selection" value="1" class="form-check-input" id="avatar" @checked(old('enable_avatar_selection', $settings['enable_avatar_selection'] ?? true))>
                    <label for="avatar" class="form-check-label">Enable Avatar Selection</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check">
                    <input type="checkbox" name="image_moderation_enabled" value="1" class="form-check-input" id="moderation" @checked(old('image_moderation_enabled', $settings['image_moderation_enabled'] ?? false))>
                    <label for="moderation" class="form-check-label">Image Moderation</label>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Max Image Size (MB)</label>
                <input type="number" name="max_image_size_mb" class="form-control" value="{{ old('max_image_size_mb', $settings['max_image_size_mb'] ?? 5) }}">
            </div>
            <div class="col-md-8">
                <label class="form-label">Allowed Image Types (comma-separated MIME)</label>
                <input name="allowed_image_types" class="form-control" value="{{ old('allowed_image_types', implode(',', $settings['allowed_image_types'] ?? ['image/jpeg','image/png','image/webp'])) }}">
            </div>
        </div>

        <h5 class="text-white mb-3">Challenge Limits</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">Challenge Expiry (hours)</label>
                <input type="number" name="challenge_expiry_hours" class="form-control" value="{{ old('challenge_expiry_hours', $settings['challenge_expiry_hours'] ?? 168) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Max Rematches</label>
                <input type="number" name="max_rematches" class="form-control" value="{{ old('max_rematches', $settings['max_rematches'] ?? 10) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Max Shares (0 = unlimited)</label>
                <input type="number" name="max_shares" class="form-control" value="{{ old('max_shares', $settings['max_shares'] ?? 0) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Media Expiry (hours, 0 = never)</label>
                <input type="number" name="media_expiry_hours" class="form-control" value="{{ old('media_expiry_hours', $settings['media_expiry_hours'] ?? 0) }}">
            </div>
        </div>

        <button type="submit" class="btn btn-admin-primary">Save Settings</button>
    </form>
</div>
@endsection
