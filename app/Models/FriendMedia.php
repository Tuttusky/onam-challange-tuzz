<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FriendMedia extends Model
{
    protected $table = 'friend_media';

    protected $fillable = [
        'uuid',
        'type',
        'storage_path',
        'public_token',
        'friend_avatar_id',
        'initial',
        'mime',
        'size',
        'width',
        'height',
        'player_id',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (FriendMedia $media) {
            if (empty($media->uuid)) {
                $media->uuid = (string) Str::uuid();
            }

            if (empty($media->public_token)) {
                $media->public_token = Str::random(48);
            }
        });
    }

    public function avatar(): BelongsTo
    {
        return $this->belongsTo(FriendAvatar::class, 'friend_avatar_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && now()->gt($this->expires_at);
    }
}
