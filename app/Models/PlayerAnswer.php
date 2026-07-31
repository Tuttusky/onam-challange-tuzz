<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerAnswer extends Model
{
    protected $fillable = [
        'player_session_id',
        'question_id',
        'question_option_id',
        'answer_text',
        'answer_media',
        'points',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PlayerSession::class, 'player_session_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class, 'question_option_id');
    }
}
