<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallengeShare extends Model
{
    protected $fillable = [
        'challenge_link_id',
        'player_id',
        'channel',
    ];

    public function challengeLink(): BelongsTo
    {
        return $this->belongsTo(ChallengeLink::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
