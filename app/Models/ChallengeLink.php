<?php

namespace App\Models;

use App\Services\ChallengeLinkService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ChallengeLink extends Model
{
    protected $fillable = [
        'code',
        'token',
        'campaign_id',
        'creator_session_id',
        'friend_name',
        'challenge_title',
        'challenge_message',
        'question_ids',
        'friend_media_id',
        'pottu_image_id',
        'creator_score',
        'creator_completion_time_ms',
        'max_rematches',
        'parent_link_id',
        'share_count',
        'is_active',
        'is_finalized',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_finalized' => 'boolean',
            'share_count' => 'integer',
            'creator_score' => 'decimal:2',
            'creator_completion_time_ms' => 'integer',
            'max_rematches' => 'integer',
            'expires_at' => 'datetime',
            'question_ids' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ChallengeLink $link) {
            if (empty($link->code)) {
                $link->code = strtoupper(Str::random(8));
            }

            if (empty($link->token)) {
                $link->token = Str::random(48);
            }
        });
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function creatorSession(): BelongsTo
    {
        return $this->belongsTo(PlayerSession::class, 'creator_session_id');
    }

    public function friendMedia(): BelongsTo
    {
        return $this->belongsTo(FriendMedia::class);
    }

    public function pottuImage(): BelongsTo
    {
        return $this->belongsTo(PottuImage::class);
    }

    public function parentLink(): BelongsTo
    {
        return $this->belongsTo(ChallengeLink::class, 'parent_link_id');
    }

    public function childLinks(): HasMany
    {
        return $this->hasMany(ChallengeLink::class, 'parent_link_id');
    }

    public function challengerSessions(): HasMany
    {
        return $this->hasMany(PlayerSession::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ChallengeResult::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(ChallengeShare::class);
    }

    public function rematchesFrom(): HasMany
    {
        return $this->hasMany(ChallengeRematch::class, 'from_link_id');
    }

    public function challengerCount(): int
    {
        return $this->challengerSessions()
            ->where('role', 'challenger')
            ->where('status', 'completed')
            ->count();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && now()->gt($this->expires_at);
    }

    public function isUsable(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }

    public function canAcceptMoreChallengers(Campaign $campaign): bool
    {
        return $this->challengerCount() < $campaign->max_friends;
    }

    public static function findByPublicToken(string $identifier): ?self
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        if (strlen($identifier) === 8 && preg_match('/^[A-Z0-9]{8}$/i', $identifier) === 1) {
            $legacy = static::query()
                ->where('code', strtoupper($identifier))
                ->first();

            if ($legacy) {
                return $legacy;
            }
        }

        return static::query()
            ->where('token', $identifier)
            ->first();
    }

    public static function findByPublicTokenOrFail(string $identifier): self
    {
        return static::findByPublicToken($identifier)
            ?? throw (new ModelNotFoundException)->setModel(static::class);
    }

    public function ensureShareToken(): string
    {
        if (! empty($this->token)) {
            return $this->token;
        }

        $this->forceFill([
            'token' => app(ChallengeLinkService::class)->generateSecureToken(),
        ])->save();

        return $this->token;
    }

    public function sharePath(): string
    {
        return '/challenge/'.$this->ensureShareToken();
    }
}
