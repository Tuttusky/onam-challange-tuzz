<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameScore extends Model
{
    protected $fillable = [
        'player_session_id',
        'score',
        'completion_time_ms',
        'accuracy',
        'achievements',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'completion_time_ms' => 'integer',
            'accuracy' => 'decimal:2',
            'achievements' => 'array',
            'meta' => 'array',
        ];
    }

    public function playerSession(): BelongsTo
    {
        return $this->belongsTo(PlayerSession::class);
    }
}
