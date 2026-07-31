<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SubmitPottuPlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'image_id' => ['required', 'integer', 'min:1'],
            'style_id' => ['nullable', 'integer', 'min:1'],
            'x' => ['required', 'numeric', 'min:0', 'max:1'],
            'y' => ['required', 'numeric', 'min:0', 'max:1'],
            'size' => ['required', 'integer', 'min:16', 'max:200'],
            'rotation' => ['nullable', 'numeric', 'min:-360', 'max:360'],
            'board_width' => ['required', 'integer', 'min:100', 'max:4000'],
            'board_height' => ['required', 'integer', 'min:100', 'max:4000'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toHandlerPayload(): array
    {
        return [
            'placement' => [
                'image_id' => (int) $this->validated('image_id'),
                'style_id' => $this->validated('style_id') ? (int) $this->validated('style_id') : null,
                'x' => (float) $this->validated('x'),
                'y' => (float) $this->validated('y'),
                'size' => (int) $this->validated('size'),
                'rotation' => (float) ($this->validated('rotation') ?? 0),
                'board_width' => (int) $this->validated('board_width'),
                'board_height' => (int) $this->validated('board_height'),
            ],
        ];
    }
}
