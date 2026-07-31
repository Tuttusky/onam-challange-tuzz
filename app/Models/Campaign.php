<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use SoftDeletes;

    public const TYPE_DARE_CHALLENGE = 'dare_challenge';

    public const TYPE_QUIZ = 'quiz';

    public const TYPE_POLL = 'poll';

    public const TYPE_SURVEY = 'survey';

    public const TYPE_POTTU = 'sundarikk_pottu';

    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
        'status',
        'starts_at',
        'ends_at',
        'max_questions',
        'max_friends',
        'share_message',
        'default_challenge_title',
        'campaign_theme_id',
        'is_featured',
        'sort_order',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_featured' => 'boolean',
            'max_questions' => 'integer',
            'max_friends' => 'integer',
            'sort_order' => 'integer',
            'settings' => 'array',
        ];
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(CampaignTheme::class, 'campaign_theme_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(QuestionCategory::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function badges(): HasMany
    {
        return $this->hasMany(Badge::class);
    }

    public function resultMessages(): HasMany
    {
        return $this->hasMany(ResultMessage::class);
    }

    public function playerSessions(): HasMany
    {
        return $this->hasMany(PlayerSession::class);
    }

    public function challengeLinks(): HasMany
    {
        return $this->hasMany(ChallengeLink::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    public function leaderboardSnapshots(): HasMany
    {
        return $this->hasMany(LeaderboardSnapshot::class);
    }

    public function visitEvents(): HasMany
    {
        return $this->hasMany(VisitEvent::class);
    }

    public function pottuImages(): HasMany
    {
        return $this->hasMany(PottuImage::class);
    }

    public function pottuStyles(): HasMany
    {
        return $this->hasMany(PottuStyle::class);
    }

    public function isPottu(): bool
    {
        return $this->type === self::TYPE_POTTU;
    }

    public function seoSettings(): MorphMany
    {
        return $this->morphMany(SeoSetting::class, 'seoable');
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }
}
