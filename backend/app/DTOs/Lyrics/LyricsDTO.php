<?php

namespace App\DTOs\Lyrics;

use JsonSerializable;

readonly class LyricsDTO implements JsonSerializable
{
    /**
     * @param string $id
     * @param string $provider
     * @param string $type 'plain' | 'line_synced' | 'word_synced' | 'syllable_synced'
     * @param array<int, array{start_ms: int|null, end_ms: int|null, text: string, words?: array<int, array{start_ms: int, end_ms: int, text: string}>}> $lines
     * @param string|null $language
     * @param string|null $raw
     * @param float|null $score
     * @param array<string, mixed> $extra
     */
    public function __construct(
        public string $id,
        public string $provider,
        public string $type,
        public array $lines,
        public ?string $language = null,
        public ?string $raw = null,
        public ?float $score = null,
        public array $extra = []
    ) {}

    public function toArray(): array
    {
        return [
            'id'       => $this->id,
            'provider' => $this->provider,
            'type'     => $this->type,
            'language' => $this->language,
            'score'    => $this->score,
            'lines'    => $this->lines,
            'raw'      => $this->raw,
            'extra'    => $this->extra,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            provider: (string) ($data['provider'] ?? 'unknown'),
            type: (string) ($data['type'] ?? 'plain'),
            lines: $data['lines'] ?? [],
            language: $data['language'] ?? null,
            raw: $data['raw'] ?? null,
            score: isset($data['score']) ? (float) $data['score'] : null,
            extra: $data['extra'] ?? []
        );
    }
}
