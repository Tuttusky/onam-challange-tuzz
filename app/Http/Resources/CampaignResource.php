<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Campaign */
class CampaignResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'description' => $this->description,
            'status' => $this->status,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'max_questions' => $this->max_questions,
            'max_friends' => $this->max_friends,
            'share_message' => $this->share_message,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,
            'is_active' => $this->isActive(),
            'theme' => CampaignThemeResource::make($this->whenLoaded('theme')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
