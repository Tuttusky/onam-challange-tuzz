<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PlayerSession */
class PlayerSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'role' => $this->role,
            'status' => $this->status,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'player' => $this->whenLoaded('player', fn () => [
                'uuid' => $this->player->uuid,
                'name' => $this->player->name,
                'referral_code' => $this->player->referral_code,
            ]),
            'campaign' => CampaignResource::make($this->whenLoaded('campaign')),
            'challenge_link' => ChallengeLinkResource::make($this->whenLoaded('challengeLink')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
