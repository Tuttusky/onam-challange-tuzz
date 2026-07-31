<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\CampaignTheme */
class CampaignThemeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo' => $this->logo,
            'favicon' => $this->favicon,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'background_image' => $this->background_image,
            'background_gradient' => $this->background_gradient,
            'font_family' => $this->font_family,
            'animation_pack' => $this->animation_pack,
            'sound_pack' => $this->sound_pack,
        ];
    }
}
