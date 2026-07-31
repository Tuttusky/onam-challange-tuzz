<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitAnswersRequest extends FormRequest
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
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer', 'min:1'],
            'answers.*.option_id' => ['nullable', 'integer', 'min:1'],
            'answers.*.answer_text' => ['nullable', 'string', 'max:5000'],
            'answers.*.answer_media' => ['nullable', 'string', 'max:2048'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->input('answers', []) as $index => $answer) {
                $hasOption = ! empty($answer['option_id']);
                $hasText = ! empty($answer['answer_text']);
                $hasMedia = ! empty($answer['answer_media']);

                if (! $hasOption && ! $hasText && ! $hasMedia) {
                    $validator->errors()->add(
                        "answers.{$index}",
                        'Each answer must include an option_id, answer_text, or answer_media.'
                    );
                }
            }
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function normalizedAnswers(): array
    {
        return collect($this->validated('answers'))
            ->map(fn (array $answer) => [
                'question_id' => (int) $answer['question_id'],
                'question_option_id' => isset($answer['option_id']) ? (int) $answer['option_id'] : null,
                'answer_text' => $answer['answer_text'] ?? null,
                'answer_media' => $answer['answer_media'] ?? null,
            ])
            ->values()
            ->all();
    }
}
