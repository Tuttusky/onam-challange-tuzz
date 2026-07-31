<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Player extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'referral_code',
        'referred_by_player_id',
        'device',
        'browser',
        'ip',
        'country',
        'share_count',
    ];

    protected function casts(): array
    {
        return [
            'share_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Player $player) {
            if (empty($player->uuid)) {
                $player->uuid = (string) Str::uuid();
            }

            if (empty($player->referral_code)) {
                $player->referral_code = strtoupper(Str::random(8));
            }
        });
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'referred_by_player_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Player::class, 'referred_by_player_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PlayerSession::class);
    }

    public function answers(): HasManyThrough
    {
        return $this->hasManyThrough(PlayerAnswer::class, PlayerSession::class);
    }

    public function referredBy(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_player_id');
    }

    public function referredAs(): HasMany
    {
        return $this->hasMany(Referral::class, 'referred_player_id');
    }

    public function leaderboardSnapshots(): HasMany
    {
        return $this->hasMany(LeaderboardSnapshot::class);
    }

    public function visitEvents(): HasMany
    {
        return $this->hasMany(VisitEvent::class);
    }

    public function wonResults(): HasMany
    {
        return $this->hasMany(ChallengeResult::class, 'winner_player_id');
    }
}
