<?php

namespace App\Http\Resources;

use App\Services\AnalyticsService;
use App\Services\WebsiteSettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicSettingsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $analyticsService = app(AnalyticsService::class);

        return [
            'site_name' => WebsiteSettingsService::getSiteName(),
            'site_tagline' => WebsiteSettingsService::getSiteTagline(),
            'contact_email' => WebsiteSettingsService::getContactEmail(),
            'social_links' => WebsiteSettingsService::getSocialLinks(),
            'branding' => WebsiteSettingsService::getBranding(),
            'feature_flags' => WebsiteSettingsService::getFeatureFlags(),
            'referral_reward_points' => WebsiteSettingsService::getReferralRewardPoints(),
            'default_share_message' => WebsiteSettingsService::get('default_share_message'),
            'max_upload_size_mb' => (int) WebsiteSettingsService::get('max_upload_size_mb', 5),
            'friend_challenge' => WebsiteSettingsService::getFriendChallengeSettings(),
            'pottu' => WebsiteSettingsService::getPottuSettings(),
            'tracking' => $analyticsService->getTrackingScripts(),
        ];
    }
}
