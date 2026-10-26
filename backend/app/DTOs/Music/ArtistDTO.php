<?php

namespace App\DTOs\Music;

use JsonSerializable;

readonly class ArtistDTO implements JsonSerializable
{
    /**
     * @param string $id
     * @param string $provider
     * @param string $provider_id
     * @param string $name
     * @param string|null $role
     * @param array{small: string|null, medium: string|null, large: string|null} $image
     * @param string|null $bio
     * @param int|null $follower_count
     * @param array<int, SongDTO> $top_songs
     * @param array<int, AlbumDTO> $top_albums
     * @param array<string, mixed> $extra
     */
    public function __construct(
        public string $id,
        public string $provider,
        public string $provider_id,
        public string $name,
        public ?string $role = null,
        public array $image = ['small' => null, 'medium' => null, 'large' => null],
        public ?string $bio = null,
        public ?int $follower_count = null,
        public array $top_songs = [],
        public array $top_albums = [],
        public array $extra = []
    ) {}

    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'provider'       => $this->provider,
            'provider_id'    => $this->provider_id,
            'name'           => $this->name,
            'role'           => $this->role,
            'image'          => $this->image,
            'bio'            => $this->bio,
            'follower_count' => $this->follower_count,
            'top_songs'      => array_map(fn($song) => $song instanceof SongDTO ? $song->toArray() : $song, $this->top_songs),
            'top_albums'     => array_map(fn($album) => $album instanceof AlbumDTO ? $album->toArray() : $album, $this->top_albums),
            'extra'          => $this->extra,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
