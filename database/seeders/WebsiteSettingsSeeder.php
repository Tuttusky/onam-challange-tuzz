<?php

namespace Database\Seeders;

use App\Models\WebsiteSetting;
use Illuminate\Database\Seeder;

class WebsiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'site_name',
                'value' => 'Onam Dare Challenge',
                'group' => 'general',
            ],
            [
                'key' => 'site_tagline',
                'value' => 'Challenge your friends this Onam!',
                'group' => 'general',
            ],
            [
                'key' => 'contact_email',
                'value' => 'hello@onamdare.com',
                'group' => 'general',
            ],
            [
                'key' => 'support_email',
                'value' => 'support@onamdare.com',
                'group' => 'general',
            ],
            [
                'key' => 'referral_reward_points',
                'value' => 10,
                'group' => 'general',
            ],
            [
                'key' => 'maintenance_mode',
                'value' => false,
                'group' => 'general',
            ],
            [
                'key' => 'social_links',
                'value' => [
                    'facebook' => 'https://facebook.com/onamdare',
                    'instagram' => 'https://instagram.com/onamdare',
                    'whatsapp' => 'https://wa.me/919000000000',
                    'youtube' => 'https://youtube.com/@onamdare',
                ],
                'group' => 'social',
            ],
            [
                'key' => 'logo',
                'value' => '/images/branding/logo.png',
                'group' => 'branding',
            ],
            [
                'key' => 'favicon',
                'value' => '/images/branding/favicon.ico',
                'group' => 'branding',
            ],
            [
                'key' => 'primary_color',
                'value' => '#6366f1',
                'group' => 'branding',
            ],
            [
                'key' => 'secondary_color',
                'value' => '#004E89',
                'group' => 'branding',
            ],
            [
                'key' => 'footer_text',
                'value' => '© '.date('Y').' Onam Dare Challenge. All rights reserved.',
                'group' => 'general',
            ],
            [
                'key' => 'feature_flags',
                'value' => [
                    'referrals' => true,
                    'leaderboard' => true,
                    'analytics' => true,
                    'share_cards' => true,
                ],
                'group' => 'features',
            ],
            [
                'key' => 'default_share_message',
                'value' => 'I dare you to take the Onam Challenge! Can you match my answers? 🎉',
                'group' => 'campaign',
            ],
            [
                'key' => 'max_upload_size_mb',
                'value' => 5,
                'group' => 'general',
            ],
            [
                'key' => 'friend_challenge_settings',
                'value' => [
                    'enable_photo_upload' => true,
                    'enable_avatar_selection' => true,
                    'max_image_size_mb' => 5,
                    'allowed_image_types' => ['image/jpeg', 'image/png', 'image/webp'],
                    'image_moderation_enabled' => false,
                    'challenge_expiry_hours' => 168,
                    'max_rematches' => 10,
                    'max_shares' => 0,
                    'media_expiry_hours' => 0,
                ],
                'group' => 'friend_challenge',
            ],
            [
                'key' => 'pottu_settings',
                'value' => \App\Services\Pottu\PottuSettingsService::defaults(),
                'group' => 'pottu',
            ],
        ];

        foreach ($settings as $setting) {
            WebsiteSetting::query()->updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'group' => $setting['group'],
                ]
            );
        }
    }
}
