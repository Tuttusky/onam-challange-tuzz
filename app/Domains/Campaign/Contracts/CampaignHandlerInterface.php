<?php

namespace App\Domains\Campaign\Contracts;

use App\Domains\Campaign\DTO\ScorePayload;
use App\Models\Campaign;
use App\Models\PlayerSession;

interface CampaignHandlerInterface
{
    /**
     * @return array<string, mixed>
     */
    public function bootstrapPlay(Campaign $campaign, PlayerSession $session): array;

    /**
     * @param  array<int, array<string, mixed>>  $answers
     * @return array<string, mixed>
     */
    public function submitPlay(Campaign $campaign, PlayerSession $session, array $answers): array;

    public function finalizePlay(Campaign $campaign, PlayerSession $session): ScorePayload;

    public function comparePlay(
        Campaign $campaign,
        PlayerSession $creatorSession,
        PlayerSession $challengerSession
    ): ScorePayload;
}
