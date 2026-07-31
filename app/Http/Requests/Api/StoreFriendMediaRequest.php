<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFriendMediaRequest extends FormRequest
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
            'media_type' => ['required', Rule::in(['upload', 'avatar', 'initial'])],
            'photo' => ['required_if:media_type,upload', 'file', 'image', 'max:10240'],
            'avatar_id' => ['required_if:media_type,avatar', 'integer', 'exists:friend_avatars,id'],
            'initial' => ['required_if:media_type,initial', 'string', 'min:1', 'max:2'],
            'player_uuid' => ['nullable', 'uuid'],
            'name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
