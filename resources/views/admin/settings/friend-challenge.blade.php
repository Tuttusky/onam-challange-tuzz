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

        <h5 class="text-white mb-3">How to Play Popup (Friend Invites)</h5>
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" name="show_how_to_play_popup" value="1" class="form-check-input" id="popup_enabled" @checked(old('show_how_to_play_popup', $settings['show_how_to_play_popup'] ?? true))>
                    <label for="popup_enabled" class="form-check-label">Show "How to Play" Popup when friend enters name on invite page</label>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Popup Title</label>
                <input type="text" name="how_to_play_title" class="form-control" value="{{ old('how_to_play_title', $settings['how_to_play_title'] ?? 'How to Play This Challenge 🎯') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Popup Subtitle / Introduction</label>
                <input type="text" name="how_to_play_content" class="form-control" value="{{ old('how_to_play_content', $settings['how_to_play_content'] ?? 'Follow these quick steps to beat your friend\'s score:') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Step 1 Instruction</label>
                <input type="text" name="how_to_play_step_1" class="form-control" value="{{ old('how_to_play_step_1', $settings['how_to_play_step_1'] ?? 'Enter your name & accept the challenge') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Step 2 Instruction</label>
                <input type="text" name="how_to_play_step_2" class="form-control" value="{{ old('how_to_play_step_2', $settings['how_to_play_step_2'] ?? 'Drag the pottu dot to the forehead within 30 seconds') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Step 3 Instruction</label>
                <input type="text" name="how_to_play_step_3" class="form-control" value="{{ old('how_to_play_step_3', $settings['how_to_play_step_3'] ?? 'Check your live accuracy score and beat your friend!') }}">
            </div>
        </div>

        <button type="submit" class="btn btn-admin-primary">Save Settings</button>
    </form>
</div>
@endsection
