<?php

namespace App\Services\Pottu;

use App\Models\Campaign;
use App\Services\WebsiteSettingsService;

class PottuSettingsService
{
    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'enable_game' => true,
            'overlay_enabled' => true,
            'overlay_color' => '#FFFFFF',
            'overlay_opacity' => 1.0,
            'reveal_speed_ms' => 200,
            'fail_threshold_px' => 30,
            'max_attempts' => 5,
            'time_limit_sec' => 30,
            'leaderboard_enabled' => true,
            'coupon_enabled' => true,
            'analytics_enabled' => true,
            'custom_image_retention_days' => 7,
            'custom_challenge_valid_days' => 7,
            'rewards' => [
                'coupon' => true,
                'lucky_draw' => true,
                'points' => false,
                'badge' => true,
            ],
            'tolerance_bands' => [
                ['min' => 0, 'max' => 5, 'stars' => 5, 'label' => 'Perfect', 'points' => 100],
                ['min' => 6, 'max' => 10, 'stars' => 4, 'label' => 'Excellent', 'points' => 90],
                ['min' => 11, 'max' => 20, 'stars' => 3, 'label' => 'Good', 'points' => 75],
                ['min' => 21, 'max' => 30, 'stars' => 2, 'label' => 'Average', 'points' => 50],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function globalSettings(): array
    {
        $stored = WebsiteSettingsService::get('pottu_settings');

        if (is_array($stored) && ! empty($stored)) {
            return array_replace_recursive(static::defaults(), $stored);
        }

        $group = WebsiteSettingsService::getGroup('pottu');

        return array_replace_recursive(static::defaults(), $group ?: []);
    }

    /**
     * @return array<string, mixed>
     */
    public static function forCampaign(Campaign $campaign): array
    {
        $campaignSettings = is_array($campaign->settings) ? $campaign->settings : [];
        $pottu = $campaignSettings['pottu'] ?? [];

        return array_replace_recursive(static::globalSettings(), $pottu);
    }

    /**
     * @return array<string, mixed>
     */
    public static function publicForCampaign(Campaign $campaign): array
    {
        $settings = static::forCampaign($campaign);

        return [
            'enable_game' => (bool) ($settings['enable_game'] ?? true),
            'overlay_enabled' => (bool) ($settings['overlay_enabled'] ?? true),
            'overlay_color' => (string) ($settings['overlay_color'] ?? '#FFFFFF'),
            'overlay_opacity' => (float) ($settings['overlay_opacity'] ?? 1.0),
            'reveal_speed_ms' => (int) ($settings['reveal_speed_ms'] ?? 200),
            'max_attempts' => (int) ($settings['max_attempts'] ?? 5),
            'time_limit_sec' => $settings['time_limit_sec'] ?? null,
            'fail_threshold_px' => (int) ($settings['fail_threshold_px'] ?? 30),
            'tolerance_bands' => $settings['tolerance_bands'] ?? static::defaults()['tolerance_bands'],
            'leaderboard_enabled' => (bool) ($settings['leaderboard_enabled'] ?? true),
            'custom_image_retention_days' => (int) ($settings['custom_image_retention_days'] ?? 7),
            'custom_challenge_valid_days' => (int) ($settings['custom_challenge_valid_days'] ?? 7),
            'custom_photo_privacy_notice' => 'Custom photos are stored for 7 days only, then automatically deleted from our servers. Custom photo challenges are valid for 7 days.',
        ];
    }
}
