<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\ChallengeLink;
use App\Models\FriendMedia;
use App\Services\WebsiteSettingsService;
use Illuminate\Support\Str;

class ChallengeLinkService
{
    public function buildTitle(Campaign $campaign, string $friendName, ?string $customTitle = null): string
    {
        if ($customTitle) {
            return $customTitle;
        }

        $template = $campaign->default_challenge_title
            ?? 'Hey {friend_name}, Can You Beat Me?';

        return str_replace('{friend_name}', $friendName, $template);
    }

    public function resolveExpiry(): ?\DateTimeInterface
    {
        $hours = (int) WebsiteSettingsService::getFriendChallengeSetting('challenge_expiry_hours', 168);

        if ($hours <= 0) {
            return null;
        }

        return now()->addHours($hours);
    }

    public function applyPersonalization(ChallengeLink $link, array $data): ChallengeLink
    {
        $link->update([
            'friend_name' => $data['friend_name'] ?? $link->friend_name,
            'challenge_title' => $data['challenge_title'] ?? $link->challenge_title,
            'challenge_message' => $data['challenge_message'] ?? $link->challenge_message,
            'friend_media_id' => $data['friend_media_id'] ?? $link->friend_media_id,
            'max_rematches' => (int) WebsiteSettingsService::getFriendChallengeSetting('max_rematches', 10),
            'expires_at' => $link->expires_at ?? $this->resolveExpiry(),
        ]);

        return $link->fresh();
    }

    public function canShare(ChallengeLink $link): bool
    {
        $maxShares = (int) WebsiteSettingsService::getFriendChallengeSetting('max_shares', 0);

        if ($maxShares <= 0) {
            return true;
        }

        return $link->share_count < $maxShares;
    }

    public function canRematch(ChallengeLink $link): bool
    {
        $rematchCount = $link->rematchesFrom()->count();

        return $rematchCount < $link->max_rematches;
    }

    public function attachFriendMedia(ChallengeLink $link, ?int $friendMediaId): void
    {
        if (! $friendMediaId) {
            return;
        }

        $media = FriendMedia::query()->find($friendMediaId);

        if ($media && ! $media->isExpired()) {
            $link->update(['friend_media_id' => $media->id]);
        }
    }

    public function generateSecureToken(): string
    {
        return Str::random(48);
    }

    public function findByPublicToken(string $identifier): ChallengeLink
    {
        return ChallengeLink::findByPublicTokenOrFail($identifier);
    }
}
