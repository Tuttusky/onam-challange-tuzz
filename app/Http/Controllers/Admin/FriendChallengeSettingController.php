<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WebsiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FriendChallengeSettingController extends Controller
{
    public function edit(): View
    {
        $settings = WebsiteSettingsService::getFriendChallengeSettings();

        return view('admin.settings.friend-challenge', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enable_photo_upload' => ['sometimes', 'boolean'],
            'enable_avatar_selection' => ['sometimes', 'boolean'],
            'max_image_size_mb' => ['nullable', 'integer', 'min:1', 'max:20'],
            'allowed_image_types' => ['nullable', 'string'],
            'image_moderation_enabled' => ['sometimes', 'boolean'],
            'challenge_expiry_hours' => ['nullable', 'integer', 'min:0', 'max:8760'],
            'max_rematches' => ['nullable', 'integer', 'min:0', 'max:100'],
            'max_shares' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'media_expiry_hours' => ['nullable', 'integer', 'min:0', 'max:8760'],
        ]);

        $types = array_values(array_filter(array_map('trim', explode(',', (string) ($data['allowed_image_types'] ?? 'image/jpeg,image/png,image/webp')))));

        WebsiteSettingsService::set('friend_challenge_settings', [
            'enable_photo_upload' => $request->boolean('enable_photo_upload', true),
            'enable_avatar_selection' => $request->boolean('enable_avatar_selection', true),
            'max_image_size_mb' => (int) ($data['max_image_size_mb'] ?? 5),
            'allowed_image_types' => $types ?: ['image/jpeg', 'image/png', 'image/webp'],
            'image_moderation_enabled' => $request->boolean('image_moderation_enabled', false),
            'challenge_expiry_hours' => (int) ($data['challenge_expiry_hours'] ?? 168),
            'max_rematches' => (int) ($data['max_rematches'] ?? 10),
            'max_shares' => (int) ($data['max_shares'] ?? 0),
            'media_expiry_hours' => (int) ($data['media_expiry_hours'] ?? 0),
        ], 'friend_challenge');

        WebsiteSettingsService::flushCache();

        return back()->with('success', 'Friend challenge settings updated.');
    }
}
