<?php

namespace App\Domains\Campaign\Handlers;

use App\Domains\Campaign\Contracts\CampaignHandlerInterface;
use App\Domains\Campaign\DTO\ScorePayload;
use App\Models\Campaign;
use App\Models\PlayerSession;
use RuntimeException;

class StubCampaignHandler implements CampaignHandlerInterface
{
    public function bootstrapPlay(Campaign $campaign, PlayerSession $session): array
    {
        throw new RuntimeException('This campaign type is coming soon.');
    }

    public function submitPlay(Campaign $campaign, PlayerSession $session, array $answers): array
    {
        throw new RuntimeException('This campaign type is coming soon.');
    }

    public function finalizePlay(Campaign $campaign, PlayerSession $session): ScorePayload
    {
        throw new RuntimeException('This campaign type is coming soon.');
    }

    public function comparePlay(
        Campaign $campaign,
        PlayerSession $creatorSession,
        PlayerSession $challengerSession
    ): ScorePayload {
        throw new RuntimeException('This campaign type is coming soon.');
    }
}
