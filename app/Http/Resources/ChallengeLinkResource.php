<?php

namespace App\Http\Resources;

use App\Services\FriendMediaService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ChallengeLink */
class ChallengeLinkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $mediaService = app(FriendMediaService::class);

        return [
            'code' => $this->code,
            'token' => $this->ensureShareToken(),
            'share_count' => $this->share_count,
            'is_active' => $this->is_active,
            'is_finalized' => $this->is_finalized,
            'is_usable' => $this->isUsable(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'friend_name' => $this->friend_name,
            'challenge_title' => $this->challenge_title,
            'challenge_message' => $this->challenge_message,
            'creator_score' => $this->creator_score !== null ? (float) $this->creator_score : null,
            'creator_completion_time_ms' => $this->creator_completion_time_ms,
            'friend_media' => $this->whenLoaded('friendMedia', fn () => $mediaService->toPublicArray($this->friendMedia)),
            'campaign' => CampaignResource::make($this->whenLoaded('campaign')),
            'creator' => $this->whenLoaded('creatorSession', fn () => [
                'uuid' => $this->creatorSession->player?->uuid ?? $this->creatorSession->uuid,
                'name' => $this->creatorSession->player?->name ?? 'Player',
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
