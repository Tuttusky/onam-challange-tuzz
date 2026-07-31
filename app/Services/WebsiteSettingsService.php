<?php

namespace App\Services;

use App\Models\WebsiteSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class WebsiteSettingsService
{
    public static function get(string $key, mixed $default = null): mixed
    {
        return WebsiteSetting::get($key, $default);
    }

    public static function set(string $key, mixed $value, string $group = 'general'): WebsiteSetting
    {
        return WebsiteSetting::set($key, $value, $group);
    }

    public static function getGroup(string $group): array
    {
        return Cache::remember("website_settings.group.{$group}", 3600, function () use ($group) {
            return WebsiteSetting::getGroup($group);
        });
    }

    public static function all(): Collection
    {
        return Cache::remember('website_settings.all', 3600, function () {
            return WebsiteSetting::query()->orderBy('group')->orderBy('key')->get();
        });
    }

    public static function getSiteName(): string
    {
        return (string) static::get('site_name', config('app.name'));
    }

    public static function getSiteTagline(): ?string
    {
        return static::get('site_tagline');
    }

    public static function getContactEmail(): ?string
    {
        return static::get('contact_email');
    }

    public static function getSocialLinks(): array
    {
        return static::get('social_links', []);
    }

    public static function getReferralRewardPoints(): int
    {
        return (int) static::get('referral_reward_points', 10);
    }

    public static function getMaintenanceMode(): bool
    {
        return (bool) static::get('maintenance_mode', false);
    }

    public static function getBranding(): array
    {
        return static::getGroup('branding');
    }

    public static function getFeatureFlags(): array
    {
        return static::get('feature_flags', [
            'referrals' => true,
            'leaderboard' => true,
            'analytics' => true,
        ]);
    }

    public static function isFeatureEnabled(string $feature): bool
    {
        $flags = static::getFeatureFlags();

        return (bool) ($flags[$feature] ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getFriendChallengeSettings(): array
    {
        $defaults = [
            'enable_photo_upload' => true,
            'enable_avatar_selection' => true,
            'max_image_size_mb' => (int) static::get('max_upload_size_mb', 5),
            'allowed_image_types' => ['image/jpeg', 'image/png', 'image/webp'],
            'image_moderation_enabled' => false,
            'challenge_expiry_hours' => 168,
            'max_rematches' => 10,
            'max_shares' => 0,
            'media_expiry_hours' => 0,
            'show_how_to_play_popup' => true,
            'how_to_play_title' => 'How to Play This Challenge 🎯',
            'how_to_play_content' => 'Follow these quick steps to beat your friend\'s score:',
            'how_to_play_step_1' => 'Enter your name & accept the challenge',
            'how_to_play_step_2' => 'Drag the pottu dot to the forehead within 30 seconds',
            'how_to_play_step_3' => 'Check your live accuracy score and beat your friend!',
        ];

        if (is_array($stored) && ! empty($stored)) {
            return array_merge($defaults, $stored);
        }

        return static::getGroup('friend_challenge') ? array_merge($defaults, static::getGroup('friend_challenge')) : $defaults;
    }

    public static function getFriendChallengeSetting(string $key, mixed $default = null): mixed
    {
        $settings = static::getFriendChallengeSettings();

        return $settings[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPottuSettings(): array
    {
        return \App\Services\Pottu\PottuSettingsService::globalSettings();
    }

    public static function flushCache(): void
    {
        Cache::forget('website_settings.all');

        WebsiteSetting::query()->pluck('key')->each(function (string $key) {
            Cache::forget("website_setting.{$key}");
        });

        WebsiteSetting::query()->distinct()->pluck('group')->each(function (string $group) {
            Cache::forget("website_settings.group.{$group}");
        });
    }
}
