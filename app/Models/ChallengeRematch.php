<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallengeRematch extends Model
{
    protected $fillable = [
        'from_link_id',
        'to_link_id',
        'initiated_by_player_id',
        'type',
    ];

    public function fromLink(): BelongsTo
    {
        return $this->belongsTo(ChallengeLink::class, 'from_link_id');
    }

    public function toLink(): BelongsTo
    {
        return $this->belongsTo(ChallengeLink::class, 'to_link_id');
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'initiated_by_player_id');
    }
}
