<?php

namespace App\Services;

use App\Domains\Campaign\DTO\ScorePayload;
use App\Models\Campaign;
use App\Models\PlayerSession;

class ScoreComparisonService
{
    /**
     * @return array<string, mixed>
     */
    public function compare(
        Campaign $campaign,
        PlayerSession $creatorSession,
        PlayerSession $challengerSession,
        ScorePayload $creatorPayload,
        ScorePayload $challengerPayload
    ): array {
        $creatorScore = $creatorPayload->score;
        $friendScore = $challengerPayload->score;
        $scoreDiff = round(abs($creatorScore - $friendScore), 2);

        $winnerPlayerId = null;

        if ($friendScore > $creatorScore) {
            $winnerPlayerId = $challengerSession->player_id;
        } elseif ($creatorScore > $friendScore) {
            $winnerPlayerId = $creatorSession->player_id;
        }

        if ($campaign->type === Campaign::TYPE_DARE_CHALLENGE) {
            $matchPercent = $challengerPayload->score;
            $meta = $challengerPayload->meta;

            return [
                'creator_score' => $creatorScore,
                'friend_score' => $friendScore,
                'score_diff' => $scoreDiff,
                'accuracy' => $challengerPayload->accuracy,
                'winner_player_id' => $meta['winner_player_id'] ?? $winnerPlayerId,
                'badge' => $meta['badge'] ?? null,
                'result_message' => $meta['result_message'] ?? null,
                'match_count' => $meta['match_count'] ?? 0,
                'total_questions' => $meta['total_questions'] ?? 0,
                'match_percent' => $matchPercent,
                'details' => $meta['details'] ?? [],
            ];
        }

        if ($campaign->type === Campaign::TYPE_POTTU) {
            $meta = $challengerPayload->meta;

            return [
                'creator_score' => $creatorScore,
                'friend_score' => $friendScore,
                'score_diff' => $meta['pixel_distance'] ?? $scoreDiff,
                'accuracy' => $challengerPayload->accuracy,
                'winner_player_id' => $meta['winner_player_id'] ?? $winnerPlayerId,
                'badge' => $meta['badge'] ?? null,
                'result_message' => $meta['result_message'] ?? null,
                'match_count' => $meta['match_count'] ?? 0,
                'total_questions' => $meta['total_questions'] ?? 1,
                'match_percent' => $meta['match_percent'] ?? $friendScore,
                'pixel_distance' => $meta['pixel_distance'] ?? null,
                'band' => $meta['band'] ?? null,
                'stars' => $meta['stars'] ?? 0,
                'label' => $meta['label'] ?? null,
                'won' => $meta['won'] ?? false,
                'can_create_next_challenge' => $meta['can_create_next_challenge'] ?? false,
                'creator_position' => $meta['creator_position'] ?? null,
                'friend_position' => $meta['friend_position'] ?? null,
                'reference_size' => $meta['reference_size'] ?? null,
                'details' => $meta['details'] ?? [],
            ];
        }

        return [
            'creator_score' => $creatorScore,
            'friend_score' => $friendScore,
            'score_diff' => $scoreDiff,
            'accuracy' => $challengerPayload->accuracy,
            'winner_player_id' => $winnerPlayerId,
            'badge' => $challengerPayload->meta['badge'] ?? null,
            'result_message' => $challengerPayload->meta['result_message'] ?? null,
            'match_count' => 0,
            'total_questions' => 0,
            'match_percent' => $friendScore,
            'details' => $challengerPayload->meta['details'] ?? [],
        ];
    }
}
