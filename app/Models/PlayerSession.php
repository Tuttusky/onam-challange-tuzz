<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class PlayerSession extends Model
{
    protected $fillable = [
        'uuid',
        'campaign_id',
        'player_id',
        'role',
        'status',
        'challenge_link_id',
        'parent_session_id',
        'token_hash',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PlayerSession $session) {
            if (empty($session->uuid)) {
                $session->uuid = (string) Str::uuid();
            }

            if (empty($session->started_at)) {
                $session->started_at = now();
            }
        });
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function challengeLink(): BelongsTo
    {
        return $this->belongsTo(ChallengeLink::class);
    }

    public function parentSession(): BelongsTo
    {
        return $this->belongsTo(PlayerSession::class, 'parent_session_id');
    }

    public function childSessions(): HasMany
    {
        return $this->hasMany(PlayerSession::class, 'parent_session_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(PlayerAnswer::class);
    }

    public function gameScore(): HasOne
    {
        return $this->hasOne(GameScore::class);
    }

    public function pottuPlacement(): HasOne
    {
        return $this->hasOne(PottuPlacement::class);
    }

    public function completionTimeMs(): int
    {
        if (! $this->started_at || ! $this->completed_at) {
            return 0;
        }

        return (int) $this->started_at->diffInMilliseconds($this->completed_at);
    }

    public function createdChallengeLink(): HasOne
    {
        return $this->hasOne(ChallengeLink::class, 'creator_session_id');
    }

    public function creatorResults(): HasMany
    {
        return $this->hasMany(ChallengeResult::class, 'creator_session_id');
    }

    public function challengerResults(): HasMany
    {
        return $this->hasMany(ChallengeResult::class, 'challenger_session_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function markCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
