<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\ChallengeLink;
use App\Models\ChallengeRematch;
use App\Models\Player;
use App\Models\PlayerSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class RematchService
{
    public function __construct(
        protected ChallengeLinkService $linkService,
        protected FriendChallengeEngine $engine,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function createRematch(
        ChallengeLink $fromLink,
        Player $player,
        string $type,
        array $context = []
    ): array {
        if (! $this->linkService->canRematch($fromLink)) {
            throw new RuntimeException('Maximum rematches reached for this challenge.');
        }

        $campaign = $fromLink->campaign;

        return DB::transaction(function () use ($fromLink, $player, $type, $context, $campaign) {
            $playerData = [
                'uuid' => $player->uuid,
                'name' => $player->name,
            ];

            $personalization = [
                'friend_name' => $context['friend_name'] ?? $fromLink->friend_name,
                'challenge_title' => $context['challenge_title'] ?? $this->buildRematchTitle($fromLink, $type),
                'challenge_message' => $context['challenge_message'] ?? $fromLink->challenge_message,
                'friend_media_id' => $context['friend_media_id'] ?? $fromLink->friend_media_id,
                'parent_link_id' => $fromLink->id,
            ];

            $result = $this->engine->startCreatorSession($campaign, $playerData, $personalization);

            $newLink = ChallengeLink::query()->find($result['session']->challenge_link_id);

            if ($newLink) {
                ChallengeRematch::query()->create([
                    'from_link_id' => $fromLink->id,
                    'to_link_id' => $newLink->id,
                    'initiated_by_player_id' => $player->id,
                    'type' => $type,
                ]);
            }

            return $result;
        });
    }

    protected function buildRematchTitle(ChallengeLink $link, string $type): string
    {
        $creatorName = $link->creatorSession?->player?->name ?? 'Friend';

        return match ($type) {
            'challenge_back' => "Hey {$creatorName}, Can You Beat Me Back?",
            'rematch' => "Rematch: {$link->challenge_title}",
            default => $link->challenge_title ?? 'New Friend Challenge',
        };
    }
}
