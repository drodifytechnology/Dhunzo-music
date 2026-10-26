<?php

namespace App\DTOs\Music;

use JsonSerializable;

readonly class SongDTO implements JsonSerializable
{
    /**
     * @param string $id
     * @param string $provider
     * @param string $provider_id
     * @param string $title
     * @param string|null $subtitle
     * @param array<int, array{id: string|null, name: string, role?: string|null}> $artists
     * @param array{id: string|null, title: string|null} $album
     * @param int|null $duration Duration in seconds
     * @param string|null $language
     * @param string|null $year
     * @param array{small: string|null, medium: string|null, large: string|null} $artwork
     * @param array{type: string, url: string|null, embed_url: string|null} $playback
     * @param array<string, mixed> $extra
     */
    public function __construct(
        public string $id,
        public string $provider,
        public string $provider_id,
        public string $title,
        public ?string $subtitle = null,
        public array $artists = [],
        public array $album = ['id' => null, 'title' => null],
        public ?int $duration = null,
        public ?string $language = null,
        public ?string $year = null,
        public array $artwork = ['small' => null, 'medium' => null, 'large' => null],
        public array $playback = ['type' => 'stream', 'url' => null, 'embed_url' => null],
        public array $extra = []
    ) {}

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'provider'    => $this->provider,
            'provider_id' => $this->provider_id,
            'title'       => $this->title,
            'subtitle'    => $this->subtitle,
            'artists'     => $this->artists,
            'album'       => $this->album,
            'duration'    => $this->duration,
            'language'    => $this->language,
            'year'        => $this->year,
            'artwork'     => $this->artwork,
            'playback'    => $this->playback,
            'extra'       => $this->extra,
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
            provider_id: (string) ($data['provider_id'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            subtitle: $data['subtitle'] ?? null,
            artists: $data['artists'] ?? [],
            album: $data['album'] ?? ['id' => null, 'title' => null],
            duration: isset($data['duration']) ? (int) $data['duration'] : null,
            language: $data['language'] ?? null,
            year: isset($data['year']) ? (string) $data['year'] : null,
            artwork: $data['artwork'] ?? ['small' => null, 'medium' => null, 'large' => null],
            playback: $data['playback'] ?? ['type' => 'stream', 'url' => null, 'embed_url' => null],
            extra: $data['extra'] ?? []
        );
    }
}
