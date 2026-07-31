<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetting;
use App\Services\WebsiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebsiteSettingController extends Controller
{
    public function edit(): View
    {
        $groups = [
            'general' => WebsiteSetting::query()->where('group', 'general')->pluck('value', 'key')->toArray(),
            'branding' => WebsiteSetting::query()->where('group', 'branding')->pluck('value', 'key')->toArray(),
            'contact' => WebsiteSetting::query()->where('group', 'contact')->pluck('value', 'key')->toArray(),
            'features' => WebsiteSetting::query()->where('group', 'features')->pluck('value', 'key')->toArray(),
        ];

        return view('admin.settings.website', compact('groups'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['nullable', 'string', 'max:255'],
            'site_tagline' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'string', 'max:255'],
            'favicon' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
            'maintenance_mode' => ['sometimes', 'boolean'],
            'referral_reward_points' => ['nullable', 'integer', 'min:0'],
            'feature_referrals' => ['sometimes', 'boolean'],
            'feature_leaderboard' => ['sometimes', 'boolean'],
            'feature_analytics' => ['sometimes', 'boolean'],
        ]);

        WebsiteSettingsService::set('site_name', $data['site_name'] ?? config('app.name'), 'general');
        WebsiteSettingsService::set('site_tagline', $data['site_tagline'] ?? null, 'general');
        WebsiteSettingsService::set('maintenance_mode', $request->boolean('maintenance_mode'), 'general');
        WebsiteSettingsService::set('referral_reward_points', (int) ($data['referral_reward_points'] ?? 10), 'general');

        WebsiteSettingsService::set('logo', $data['logo'] ?? null, 'branding');
        WebsiteSettingsService::set('favicon', $data['favicon'] ?? null, 'branding');
        WebsiteSettingsService::set('primary_color', $data['primary_color'] ?? '#6366f1', 'branding');
        WebsiteSettingsService::set('secondary_color', $data['secondary_color'] ?? '#1B4332', 'branding');

        WebsiteSettingsService::set('contact_email', $data['contact_email'] ?? null, 'contact');
        WebsiteSettingsService::set('contact_phone', $data['contact_phone'] ?? null, 'contact');

        WebsiteSettingsService::set('feature_flags', [
            'referrals' => $request->boolean('feature_referrals', true),
            'leaderboard' => $request->boolean('feature_leaderboard', true),
            'analytics' => $request->boolean('feature_analytics', true),
        ], 'features');

        WebsiteSettingsService::flushCache();

        return back()->with('success', 'Website settings updated successfully.');
    }
}
