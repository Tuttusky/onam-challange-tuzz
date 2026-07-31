<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\Pottu\PottuSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignPottuSettingController extends Controller
{
    public function edit(Campaign $campaign): View
    {
        abort_unless($campaign->isPottu(), 404);

        $settings = PottuSettingsService::forCampaign($campaign);

        return view('admin.pottu.campaign-settings', compact('campaign', 'settings'));
    }

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        abort_unless($campaign->isPottu(), 404);

        $data = $request->validate([
            'enable_game' => ['sometimes', 'boolean'],
            'overlay_enabled' => ['sometimes', 'boolean'],
            'overlay_color' => ['nullable', 'string', 'max:20'],
            'overlay_opacity' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'reveal_speed_ms' => ['nullable', 'integer', 'min:50', 'max:2000'],
            'fail_threshold_px' => ['nullable', 'integer', 'min:1', 'max:500'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:50'],
            'time_limit_sec' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'leaderboard_enabled' => ['sometimes', 'boolean'],
            'coupon_enabled' => ['sometimes', 'boolean'],
            'analytics_enabled' => ['sometimes', 'boolean'],
            'reward_coupon' => ['sometimes', 'boolean'],
            'reward_lucky_draw' => ['sometimes', 'boolean'],
            'reward_points' => ['sometimes', 'boolean'],
            'reward_badge' => ['sometimes', 'boolean'],
            'tolerance_bands' => ['nullable', 'string'],
        ]);

        $bands = json_decode((string) ($data['tolerance_bands'] ?? ''), true);
        if (! is_array($bands) || $bands === []) {
            $bands = PottuSettingsService::defaults()['tolerance_bands'];
        }

        $campaignSettings = is_array($campaign->settings) ? $campaign->settings : [];
        $campaignSettings['pottu'] = [
            'enable_game' => $request->boolean('enable_game', true),
            'overlay_enabled' => $request->boolean('overlay_enabled', true),
            'overlay_color' => $data['overlay_color'] ?? '#FFFFFF',
            'overlay_opacity' => (float) ($data['overlay_opacity'] ?? 1),
            'reveal_speed_ms' => (int) ($data['reveal_speed_ms'] ?? 200),
            'fail_threshold_px' => (int) ($data['fail_threshold_px'] ?? 30),
            'max_attempts' => (int) ($data['max_attempts'] ?? 5),
            'time_limit_sec' => isset($data['time_limit_sec']) && $data['time_limit_sec'] !== ''
                ? (int) $data['time_limit_sec']
                : null,
            'leaderboard_enabled' => $request->boolean('leaderboard_enabled', true),
            'coupon_enabled' => $request->boolean('coupon_enabled', true),
            'analytics_enabled' => $request->boolean('analytics_enabled', true),
            'rewards' => [
                'coupon' => $request->boolean('reward_coupon', true),
                'lucky_draw' => $request->boolean('reward_lucky_draw', true),
                'points' => $request->boolean('reward_points', false),
                'badge' => $request->boolean('reward_badge', true),
            ],
            'tolerance_bands' => $bands,
        ];

        $campaign->update(['settings' => $campaignSettings]);

        return back()->with('success', 'Campaign pottu settings updated.');
    }
}
