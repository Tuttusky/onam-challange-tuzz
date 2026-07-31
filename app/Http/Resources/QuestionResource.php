<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Question */
class QuestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'image' => $this->image,
            'icon' => $this->icon,
            'difficulty' => $this->difficulty,
            'points' => $this->points,
            'sort_order' => $this->sort_order,
            'settings' => $this->settings,
            'options' => QuestionOptionResource::collection($this->whenLoaded('options')),
        ];
    }
}
