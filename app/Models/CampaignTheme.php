<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignTheme extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'favicon',
        'primary_color',
        'secondary_color',
        'background_image',
        'background_gradient',
        'font_family',
        'animation_pack',
        'sound_pack',
    ];

    protected function casts(): array
    {
        return [
            'animation_pack' => 'array',
            'sound_pack' => 'array',
        ];
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }
}
