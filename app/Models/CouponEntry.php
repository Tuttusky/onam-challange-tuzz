<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponEntry extends Model
{
    protected $fillable = [
        'challenge_result_id',
        'player_id',
        'status',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function challengeResult(): BelongsTo
    {
        return $this->belongsTo(ChallengeResult::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
