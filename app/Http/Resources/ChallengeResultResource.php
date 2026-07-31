<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ChallengeResult */
class ChallengeResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'match_count' => $this->match_count,
            'total_questions' => $this->total_questions,
            'match_percent' => (float) $this->match_percent,
            'creator_score' => $this->creator_score !== null ? (float) $this->creator_score : null,
            'friend_score' => $this->friend_score !== null ? (float) $this->friend_score : null,
            'score_diff' => $this->score_diff !== null ? (float) $this->score_diff : null,
            'accuracy' => $this->accuracy !== null ? (float) $this->accuracy : null,
            'creator_completion_time_ms' => $this->creator_completion_time_ms,
            'friend_completion_time_ms' => $this->friend_completion_time_ms,
            'winner' => $this->whenLoaded('winner', fn () => [
                'uuid' => $this->winner?->uuid,
                'name' => $this->winner?->name,
            ]),
            'badge' => $this->whenLoaded('badge', fn () => $this->badge ? [
                'id' => $this->badge->id,
                'name' => $this->badge->name,
                'slug' => $this->badge->slug,
                'image' => $this->badge->image,
            ] : null),
            'result_message' => $this->whenLoaded('resultMessage', fn () => $this->resultMessage ? [
                'id' => $this->resultMessage->id,
                'message' => $this->resultMessage->message,
            ] : null),
            'creator' => $this->whenLoaded('creatorSession', fn () => [
                'uuid' => $this->creatorSession->player?->uuid ?? $this->creatorSession->uuid,
                'name' => $this->creatorSession->player?->name ?? 'Player',
            ]),
            'challenger' => $this->whenLoaded('challengerSession', fn () => [
                'uuid' => $this->challengerSession->player?->uuid ?? $this->challengerSession->uuid,
                'name' => $this->challengerSession->player?->name ?? 'Friend',
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
