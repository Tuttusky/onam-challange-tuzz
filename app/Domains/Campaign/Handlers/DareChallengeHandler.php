<?php

namespace App\Domains\Campaign\Handlers;

use App\Domains\Campaign\Contracts\CampaignHandlerInterface;
use App\Domains\Campaign\DTO\ScorePayload;
use App\Models\Campaign;
use App\Models\ChallengeLink;
use App\Models\PlayerAnswer;
use App\Models\PlayerSession;
use App\Models\Question;
use App\Services\ResultService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class DareChallengeHandler implements CampaignHandlerInterface
{
    public function __construct(
        protected ResultService $resultService
    ) {}

    public function bootstrapPlay(Campaign $campaign, PlayerSession $session): array
    {
        $session->loadMissing('challengeLink');
        $questionIds = $this->resolveQuestionIds($campaign, $session);
        $questions = $this->loadQuestionsByIds($questionIds);
        $link = $session->challengeLink;
        $shuffleOptions = (bool) ($campaign->settings['shuffle_options'] ?? true);

        $payload = [
            'session_id' => $session->uuid,
            'total' => $questions->count(),
            'questions' => $questions
                ->map(fn (Question $question) => $this->formatQuestion(
                    $question,
                    $shuffleOptions ? $link : null
                ))
                ->values()
                ->all(),
            'play_settings' => [
                'time_limit_sec' => max(0, (int) ($campaign->settings['time_limit_sec'] ?? 30)),
                'shuffle_options' => $shuffleOptions,
            ],
        ];

        if ($session->role === 'challenger') {
            $payload['creator_answers'] = $this->getCreatorAnswers($session);
        }

        return $payload;
    }

    /**
     * @return array<int, int>
     */
    protected function resolveQuestionIds(Campaign $campaign, PlayerSession $session): array
    {
        $link = $session->challengeLink;

        if ($link && is_array($link->question_ids) && count($link->question_ids) > 0) {
            return array_values(array_map('intval', $link->question_ids));
        }

        if ($session->role === 'creator' && $link) {
            $existingIds = $session->answers()
                ->pluck('question_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            if (count($existingIds) > 0) {
                ChallengeLink::query()
                    ->whereKey($link->id)
                    ->update(['question_ids' => $existingIds]);

                return $existingIds;
            }

            $selected = $this->pickRandomQuestionIds($campaign);

            ChallengeLink::query()
                ->whereKey($link->id)
                ->update(['question_ids' => $selected]);

            $link->question_ids = $selected;

            return $selected;
        }

        if ($session->role === 'challenger' && $link) {
            $creatorIds = $link->creatorSession?->answers()
                ->pluck('question_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            if (count($creatorIds) > 0) {
                ChallengeLink::query()
                    ->whereKey($link->id)
                    ->update(['question_ids' => $creatorIds]);

                return $creatorIds;
            }
        }

        return $this->pickRandomQuestionIds($campaign);
    }

    /**
     * @return array<int, int>
     */
    protected function pickRandomQuestionIds(Campaign $campaign): array
    {
        $pool = Question::query()
            ->where('campaign_id', $campaign->id)
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        if ($pool->isEmpty()) {
            return [];
        }

        $count = min($campaign->max_questions, $pool->count());

        return $pool->shuffle()->take($count)->values()->all();
    }

    /**
     * @param  array<int, int>  $questionIds
     * @return Collection<int, Question>
     */
    protected function loadQuestionsByIds(array $questionIds): Collection
    {
        if (count($questionIds) === 0) {
            return collect();
        }

        $order = array_flip($questionIds);

        return Question::query()
            ->whereIn('id', $questionIds)
            ->where('is_active', true)
            ->with(['options' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->get()
            ->sortBy(fn (Question $question) => $order[$question->id] ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatQuestion(Question $question, ?ChallengeLink $link = null): array
    {
        $options = $question->options;

        if ($link && $options->count() > 1) {
            $options = $options
                ->sortBy(fn ($option) => crc32("{$link->id}:{$question->id}:{$option->id}") & 0x7FFFFFFF)
                ->values();
        }

        return [
            'id' => $question->id,
            'type' => $question->type,
            'title' => $question->title,
            'description' => $question->description,
            'image' => $question->image,
            'icon' => $question->icon,
            'difficulty' => $question->difficulty,
            'points' => $question->points,
            'options' => $options->map(fn ($option) => [
                'id' => $option->id,
                'label' => $option->label,
                'value' => $option->value,
                'icon' => $option->icon,
                'image' => $option->image,
            ])->values(),
        ];
    }

    protected function expectedQuestionCount(Campaign $campaign, PlayerSession $session): int
    {
        $session->loadMissing('challengeLink');
        $ids = $session->challengeLink?->question_ids;

        if (is_array($ids) && count($ids) > 0) {
            return count($ids);
        }

        return min(
            Question::query()
                ->where('campaign_id', $campaign->id)
                ->where('is_active', true)
                ->count(),
            $campaign->max_questions
        );
    }

    /**
     * @return array<int, array{option_id: int|null, text: string|null}>
     */
    protected function getCreatorAnswers(PlayerSession $session): array
    {
        $session->loadMissing(['challengeLink.creatorSession.answers', 'parentSession.answers']);

        $creatorSession = $session->challengeLink?->creatorSession
            ?? $session->parentSession;

        if (! $creatorSession) {
            return [];
        }

        return $creatorSession->answers
            ->mapWithKeys(fn (PlayerAnswer $answer) => [
                $answer->question_id => [
                    'option_id' => $answer->question_option_id,
                    'text' => $answer->answer_text,
                ],
            ])
            ->all();
    }

    public function submitPlay(Campaign $campaign, PlayerSession $session, array $answers): array
    {
        if ($session->isCompleted()) {
            throw new RuntimeException('Session is already completed.');
        }

        $session->loadMissing('challengeLink');
        $allowedIds = collect($this->resolveQuestionIds($campaign, $session));

        if ($allowedIds->isEmpty()) {
            $allowedIds = Question::query()
                ->where('campaign_id', $campaign->id)
                ->where('is_active', true)
                ->pluck('id');
        }

        $saved = [];

        DB::transaction(function () use ($session, $answers, $allowedIds, &$saved) {
            foreach ($answers as $answer) {
                $questionId = (int) ($answer['question_id'] ?? 0);

                if (! $allowedIds->contains($questionId)) {
                    throw new InvalidArgumentException("Invalid question ID: {$questionId}");
                }

                $record = PlayerAnswer::query()->updateOrCreate(
                    [
                        'player_session_id' => $session->id,
                        'question_id' => $questionId,
                    ],
                    [
                        'question_option_id' => $answer['question_option_id'] ?? null,
                        'answer_text' => $answer['answer_text'] ?? null,
                        'answer_media' => $answer['answer_media'] ?? null,
                        'points' => (int) ($answer['points'] ?? 0),
                    ]
                );

                $saved[] = $record;
            }

            $session->update(['status' => 'answering']);
        });

        return [
            'session_id' => $session->uuid,
            'saved_count' => count($saved),
            'answers' => collect($saved)->map(fn (PlayerAnswer $a) => [
                'question_id' => $a->question_id,
                'question_option_id' => $a->question_option_id,
                'answer_text' => $a->answer_text,
            ])->values()->all(),
        ];
    }

    public function finalizePlay(Campaign $campaign, PlayerSession $session): ScorePayload
    {
        $expectedCount = $this->expectedQuestionCount($campaign, $session);
        $answerCount = $session->answers()->count();

        if ($answerCount === 0) {
            throw new RuntimeException('Cannot finalize challenge without answers.');
        }

        if ($answerCount < $expectedCount) {
            throw new RuntimeException("Please answer all {$expectedCount} questions before finishing. ({$answerCount}/{$expectedCount} saved)");
        }

        $session->markCompleted();

        return new ScorePayload(
            score: (float) $answerCount,
            completionTimeMs: $session->fresh()->completionTimeMs(),
            accuracy: 100.0,
            achievements: ['completed_dare'],
            meta: [
                'answer_count' => $answerCount,
                'total_questions' => $expectedCount,
            ],
        );
    }

    public function comparePlay(
        Campaign $campaign,
        PlayerSession $creatorSession,
        PlayerSession $challengerSession
    ): ScorePayload {
        $comparison = $this->resultService->compareSessions($creatorSession, $challengerSession);

        return new ScorePayload(
            score: (float) $comparison['match_percent'],
            completionTimeMs: $challengerSession->completionTimeMs(),
            accuracy: (float) $comparison['match_percent'],
            achievements: $comparison['badge'] ? ['badge:'.$comparison['badge']->slug] : [],
            meta: [
                'match_count' => $comparison['match_count'],
                'total_questions' => $comparison['total_questions'],
                'match_percent' => $comparison['match_percent'],
                'winner_player_id' => $comparison['winner_player_id'],
                'badge' => $comparison['badge'],
                'result_message' => $comparison['result_message'],
                'details' => $comparison['details'],
            ],
        );
    }
}
