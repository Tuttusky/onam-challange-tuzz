<?php

namespace App\Services;

use App\Models\ChallengeLink;
use App\Models\ChallengeShare;
use App\Models\Player;

class ChallengeAnalyticsService
{
    public function recordShare(ChallengeLink $link, string $channel, ?Player $player = null): ChallengeShare
    {
        $share = ChallengeShare::query()->create([
            'challenge_link_id' => $link->id,
            'player_id' => $player?->id,
            'channel' => $channel,
        ]);

        $link->increment('share_count');
        $link->creatorSession?->player?->increment('share_count');

        return $share;
    }
}
