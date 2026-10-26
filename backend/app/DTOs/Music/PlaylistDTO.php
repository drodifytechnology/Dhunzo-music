<?php

namespace App\DTOs\Music;

use JsonSerializable;

readonly class PlaylistDTO implements JsonSerializable
{
    /**
     * @param string $id
     * @param string $provider
     * @param string $provider_id
     * @param string $title
     * @param string|null $subtitle
     * @param string|null $description
     * @param int|null $follower_count
     * @param int|null $song_count
     * @param array{small: string|null, medium: string|null, large: string|null} $artwork
     * @param array<int, SongDTO> $songs
     * @param array<string, mixed> $extra
     */
    public function __construct(
        public string $id,
        public string $provider,
        public string $provider_id,
        public string $title,
        public ?string $subtitle = null,
        public ?string $description = null,
        public ?int $follower_count = null,
        public ?int $song_count = null,
        public array $artwork = ['small' => null, 'medium' => null, 'large' => null],
        public array $songs = [],
        public array $extra = []
    ) {}

    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'provider'       => $this->provider,
            'provider_id'    => $this->provider_id,
            'title'          => $this->title,
            'subtitle'       => $this->subtitle,
            'description'    => $this->description,
            'follower_count' => $this->follower_count,
            'song_count'     => $this->song_count ?? count($this->songs),
            'artwork'        => $this->artwork,
            'songs'          => array_map(fn($song) => $song instanceof SongDTO ? $song->toArray() : $song, $this->songs),
            'extra'          => $this->extra,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
