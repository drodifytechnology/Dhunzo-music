<?php

namespace App\Services\Music\Contracts;

use App\DTOs\Music\AlbumDTO;
use App\DTOs\Music\ArtistDTO;
use App\DTOs\Music\PlaylistDTO;
use App\DTOs\Music\SongDTO;

interface MusicProviderInterface
{
    /**
     * Unique identifier for the provider (e.g. 'saavn', 'youtube').
     */
    public function getName(): string;

    /**
     * Search songs given a query string.
     *
     * @return array<int, SongDTO>
     */
    public function search(string $query, int $page = 1, int $limit = 20): array;

    /**
     * Get details of a single song by ID.
     */
    public function getSongDetails(string $id): ?SongDTO;

    /**
     * Get details of an album by ID.
     */
    public function getAlbumDetails(string $id): ?AlbumDTO;

    /**
     * Get details of an artist by ID.
     */
    public function getArtistDetails(string $id): ?ArtistDTO;

    /**
     * Get details of a playlist by ID.
     */
    public function getPlaylistDetails(string $id): ?PlaylistDTO;

    /**
     * Get recommended songs based on a song ID.
     *
     * @return array<int, SongDTO>
     */
    public function getRecommendations(string $songId, int $limit = 10): array;

    /**
     * Create a radio station and return its station ID.
     */
    public function createRadioStation(string $name, string $type = 'featured'): ?string;

    /**
     * Get next song from an active radio station.
     */
    public function getRadioSong(string $stationId): ?SongDTO;

    /**
     * Get trending songs / top searches.
     *
     * @return array<int, SongDTO>
     */
    public function getTrending(int $limit = 20): array;
}
