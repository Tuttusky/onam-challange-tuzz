<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PottuPlacement extends Model
{
    protected $fillable = [
        'player_session_id',
        'pottu_image_id',
        'pottu_style_id',
        'x',
        'y',
        'size',
        'rotation',
        'board_width',
        'board_height',
        'attempt_count',
    ];

    protected function casts(): array
    {
        return [
            'x' => 'float',
            'y' => 'float',
            'size' => 'integer',
            'rotation' => 'float',
            'board_width' => 'integer',
            'board_height' => 'integer',
            'attempt_count' => 'integer',
        ];
    }

    public function playerSession(): BelongsTo
    {
        return $this->belongsTo(PlayerSession::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(PottuImage::class, 'pottu_image_id');
    }

    public function style(): BelongsTo
    {
        return $this->belongsTo(PottuStyle::class, 'pottu_style_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toResultArray(): array
    {
        return [
            'x' => (float) $this->x,
            'y' => (float) $this->y,
            'size' => (int) $this->size,
            'rotation' => (float) $this->rotation,
            'image_id' => $this->pottu_image_id,
            'style_id' => $this->pottu_style_id,
        ];
    }
}
