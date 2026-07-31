<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderboardSnapshot extends Model
{
    protected $fillable = [
        'campaign_id',
        'period',
        'metric',
        'player_id',
        'score',
        'rank',
        'snapshot_date',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'rank' => 'integer',
            'snapshot_date' => 'date',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
