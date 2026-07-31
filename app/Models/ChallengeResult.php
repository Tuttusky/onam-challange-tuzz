<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ChallengeResult extends Model
{
    protected $fillable = [
        'uuid',
        'challenge_link_id',
        'creator_session_id',
        'challenger_session_id',
        'match_count',
        'total_questions',
        'match_percent',
        'creator_score',
        'friend_score',
        'score_diff',
        'accuracy',
        'creator_completion_time_ms',
        'friend_completion_time_ms',
        'winner_player_id',
        'badge_id',
        'result_message_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'match_count' => 'integer',
            'total_questions' => 'integer',
            'match_percent' => 'decimal:2',
            'creator_score' => 'decimal:2',
            'friend_score' => 'decimal:2',
            'score_diff' => 'decimal:2',
            'accuracy' => 'decimal:2',
            'creator_completion_time_ms' => 'integer',
            'friend_completion_time_ms' => 'integer',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ChallengeResult $result) {
            if (empty($result->uuid)) {
                $result->uuid = (string) Str::uuid();
            }
        });
    }

    public function challengeLink(): BelongsTo
    {
        return $this->belongsTo(ChallengeLink::class);
    }

    public function creatorSession(): BelongsTo
    {
        return $this->belongsTo(PlayerSession::class, 'creator_session_id');
    }

    public function challengerSession(): BelongsTo
    {
        return $this->belongsTo(PlayerSession::class, 'challenger_session_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'winner_player_id');
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(Badge::class);
    }

    public function resultMessage(): BelongsTo
    {
        return $this->belongsTo(ResultMessage::class);
    }
}
