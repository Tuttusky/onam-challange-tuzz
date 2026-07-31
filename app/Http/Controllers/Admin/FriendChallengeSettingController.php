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
            'show_how_to_play_popup' => ['sometimes', 'boolean'],
            'how_to_play_title' => ['nullable', 'string', 'max:255'],
            'how_to_play_content' => ['nullable', 'string', 'max:1000'],
            'how_to_play_step_1' => ['nullable', 'string', 'max:255'],
            'how_to_play_step_2' => ['nullable', 'string', 'max:255'],
            'how_to_play_step_3' => ['nullable', 'string', 'max:255'],
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
            'show_how_to_play_popup' => $request->boolean('show_how_to_play_popup', true),
            'how_to_play_title' => $data['how_to_play_title'] ?? 'How to Play This Challenge 🎯',
            'how_to_play_content' => $data['how_to_play_content'] ?? 'Follow these quick steps to beat your friend\'s score:',
            'how_to_play_step_1' => $data['how_to_play_step_1'] ?? 'Enter your name & accept the challenge',
            'how_to_play_step_2' => $data['how_to_play_step_2'] ?? 'Drag the pottu dot to the forehead within 30 seconds',
            'how_to_play_step_3' => $data['how_to_play_step_3'] ?? 'Check your live accuracy score and beat your friend!',
        ], 'friend_challenge');

        WebsiteSettingsService::flushCache();

        return back()->with('success', 'Friend challenge settings updated.');
    }
}
