<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\LeaderboardSnapshot */
class LeaderboardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'rank' => $this->rank,
            'score' => $this->score,
            'period' => $this->period,
            'metric' => $this->metric,
            'metric_label' => match ($this->metric) {
                'most_shared' => 'Shares',
                'most_invites' => 'Invites',
                'highest_match' => 'Best match',
                'highest_accuracy' => 'Best accuracy',
                'most_won' => 'Challenges won',
                'most_created' => 'Challenges created',
                'longest_chain' => 'Longest chain',
                default => 'Score',
            },
            'snapshot_date' => $this->snapshot_date?->toDateString(),
            'player' => $this->whenLoaded('player', fn () => [
                'uuid' => $this->player->uuid,
                'name' => $this->player->name,
            ]),
            'campaign_id' => $this->campaign_id,
        ];
    }
}
