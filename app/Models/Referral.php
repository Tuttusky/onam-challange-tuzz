<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    protected $fillable = [
        'referrer_player_id',
        'referred_player_id',
        'campaign_id',
        'reward_points',
    ];

    protected function casts(): array
    {
        return [
            'reward_points' => 'integer',
        ];
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'referrer_player_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'referred_player_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
