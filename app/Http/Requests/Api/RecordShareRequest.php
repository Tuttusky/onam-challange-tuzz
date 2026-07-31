<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordShareRequest extends FormRequest
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
            'channel' => ['required', Rule::in(['whatsapp', 'instagram', 'facebook', 'telegram', 'copy'])],
            'player_uuid' => ['nullable', 'uuid'],
        ];
    }
}
