<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\Campaign;
use App\Models\PlayerAnswer;
use App\Models\PlayerSession;
use App\Models\ResultMessage;
use Illuminate\Support\Collection;

class ResultService
{
    /**
     * @return array<string, mixed>
     */
    public function compareSessions(PlayerSession $creatorSession, PlayerSession $challengerSession): array
    {
        $creatorAnswers = $this->indexedAnswers($creatorSession);
        $challengerAnswers = $this->indexedAnswers($challengerSession);

        $questionIds = $creatorAnswers->keys()
            ->intersect($challengerAnswers->keys())
            ->values();

        $matchCount = 0;

        foreach ($questionIds as $questionId) {
            if ($this->answersMatch(
                $creatorAnswers->get($questionId),
                $challengerAnswers->get($questionId)
            )) {
                $matchCount++;
            }
        }

        $totalQuestions = $questionIds->count();
        $matchPercent = $totalQuestions > 0
            ? round(($matchCount / $totalQuestions) * 100, 2)
            : 0.0;

        $campaign = $creatorSession->campaign;
        $badge = $this->resolveBadge($campaign, $matchPercent);
        $resultMessage = $this->resolveResultMessage($campaign, $matchPercent);
        $winnerPlayerId = $this->determineWinner($creatorSession, $challengerSession, $matchPercent);

        return [
            'match_count' => $matchCount,
            'total_questions' => $totalQuestions,
            'match_percent' => $matchPercent,
            'badge' => $badge,
            'result_message' => $resultMessage,
            'winner_player_id' => $winnerPlayerId,
            'details' => $this->buildAnswerDetails($creatorAnswers, $challengerAnswers, $questionIds),
        ];
    }

    /**
     * @return Collection<int, PlayerAnswer>
     */
    protected function indexedAnswers(PlayerSession $session): Collection
    {
        return $session->answers()
            ->with(['question', 'option'])
            ->get()
            ->keyBy('question_id');
    }

    protected function answersMatch(?PlayerAnswer $creator, ?PlayerAnswer $challenger): bool
    {
        if (! $creator || ! $challenger) {
            return false;
        }

        if ($creator->question_option_id && $challenger->question_option_id) {
            return (int) $creator->question_option_id === (int) $challenger->question_option_id;
        }

        if ($creator->answer_text && $challenger->answer_text) {
            return mb_strtolower(trim($creator->answer_text)) === mb_strtolower(trim($challenger->answer_text));
        }

        if ($creator->answer_media && $challenger->answer_media) {
            return $creator->answer_media === $challenger->answer_media;
        }

        return false;
    }

    protected function resolveBadge(Campaign $campaign, float $matchPercent): ?Badge
    {
        return Badge::query()
            ->where('campaign_id', $campaign->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->first(fn (Badge $badge) => $badge->matchesPercent($matchPercent));
    }

    protected function resolveResultMessage(Campaign $campaign, float $matchPercent): ?ResultMessage
    {
        return ResultMessage::query()
            ->where('campaign_id', $campaign->id)
            ->where('is_active', true)
            ->orderBy('min_match_percent')
            ->get()
            ->first(fn (ResultMessage $message) => $message->matchesPercent($matchPercent));
    }

    protected function determineWinner(
        PlayerSession $creatorSession,
        PlayerSession $challengerSession,
        float $matchPercent
    ): ?int {
        if ($matchPercent >= 80) {
            return $challengerSession->player_id;
        }

        if ($matchPercent <= 30) {
            return $creatorSession->player_id;
        }

        return null;
    }

    /**
     * @param  Collection<int, PlayerAnswer>  $creatorAnswers
     * @param  Collection<int, PlayerAnswer>  $challengerAnswers
     * @param  Collection<int, int>  $questionIds
     * @return array<int, array<string, mixed>>
     */
    protected function buildAnswerDetails(
        Collection $creatorAnswers,
        Collection $challengerAnswers,
        Collection $questionIds
    ): array {
        return $questionIds->map(function (int $questionId) use ($creatorAnswers, $challengerAnswers) {
            $creator = $creatorAnswers->get($questionId);
            $challenger = $challengerAnswers->get($questionId);

            return [
                'question_id' => $questionId,
                'question_title' => $creator?->question?->title,
                'matched' => $this->answersMatch($creator, $challenger),
                'creator_answer' => $this->formatAnswer($creator),
                'challenger_answer' => $this->formatAnswer($challenger),
            ];
        })->values()->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function formatAnswer(?PlayerAnswer $answer): ?array
    {
        if (! $answer) {
            return null;
        }

        return [
            'option_id' => $answer->question_option_id,
            'option_label' => $answer->option?->label,
            'text' => $answer->answer_text,
            'media' => $answer->answer_media,
        ];
    }
}
