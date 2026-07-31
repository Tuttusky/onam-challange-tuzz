<?php

namespace App\Domains\Campaign\DTO;

class ScorePayload
{
    /**
     * @param  array<int, string>  $achievements
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public float $score,
        public int $completionTimeMs = 0,
        public ?float $accuracy = null,
        public array $achievements = [],
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'completion_time_ms' => $this->completionTimeMs,
            'accuracy' => $this->accuracy,
            'achievements' => $this->achievements,
            'meta' => $this->meta,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            score: (float) ($data['score'] ?? 0),
            completionTimeMs: (int) ($data['completion_time_ms'] ?? 0),
            accuracy: isset($data['accuracy']) ? (float) $data['accuracy'] : null,
            achievements: $data['achievements'] ?? [],
            meta: $data['meta'] ?? [],
        );
    }
}
