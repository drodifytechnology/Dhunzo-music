<?php

namespace App\Services\Music;

use App\DTOs\Music\AlbumDTO;
use App\DTOs\Music\ArtistDTO;
use App\DTOs\Music\PlaylistDTO;
use App\DTOs\Music\SongDTO;
use Illuminate\Support\Facades\Cache;

class MusicService
{
    protected int $searchTtl;
    protected int $detailsTtl;

    public function __construct(
        protected ProviderResolver $resolver
    ) {
        $this->searchTtl = (int) config('dhunzo.music.cache.search_ttl', 900);
        $this->detailsTtl = (int) config('dhunzo.music.cache.details_ttl', 86400);
    }

    /**
     * Search songs with caching.
     *
     * @return array{songs: array<int, SongDTO>, provider: string, page: int, limit: int}
     */
    public function search(string $query, ?string $providerName = null, int $page = 1, int $limit = 20): array
    {
        $provider = $this->resolver->resolve($providerName);
        $name = $provider->getName();

        $hash = md5(mb_strtolower(trim($query)));
        $cacheKey = "dhunzo:music:search:{$name}:{$hash}:{$page}:{$limit}";

        $songsData = Cache::remember($cacheKey, $this->searchTtl, function () use ($provider, $query, $page, $limit) {
            $songs = $provider->search($query, $page, $limit);
            return array_map(fn(SongDTO $song) => $song->toArray(), $songs);
        });

        $songs = array_map(fn(array $item) => SongDTO::fromArray($item), $songsData);

        return [
            'songs'    => $songs,
            'provider' => $name,
            'page'     => $page,
            'limit'    => $limit,
        ];
    }

    /**
     * Get song details with caching.
     */
    public function getSong(string $id, ?string $providerName = null): ?SongDTO
    {
        $provider = $this->resolver->resolveFromId($id, $providerName);
        $name = $provider->getName();

        $cacheKey = "dhunzo:music:details:{$name}:song:{$id}";

        $data = Cache::remember($cacheKey, $this->detailsTtl, function () use ($provider, $id) {
            $song = $provider->getSongDetails($id);
            return $song?->toArray();
        });

        return $data ? SongDTO::fromArray($data) : null;
    }

    /**
     * Get album details with caching.
     */
    public function getAlbum(string $id, ?string $providerName = null): ?AlbumDTO
    {
        $provider = $this->resolver->resolveFromId($id, $providerName);
        $name = $provider->getName();

        $cacheKey = "dhunzo:music:details:{$name}:album:{$id}";

        return Cache::remember($cacheKey, $this->detailsTtl, function () use ($provider, $id) {
            return $provider->getAlbumDetails($id);
        });
    }

    /**
     * Get album songs.
     */
    public function getAlbumSongs(string $id, ?string $providerName = null): array
    {
        $album = $this->getAlbum($id, $providerName);
        return $album?->songs ?? [];
    }

    /**
     * Get artist details with caching.
     */
    public function getArtist(string $id, ?string $providerName = null): ?ArtistDTO
    {
        $provider = $this->resolver->resolveFromId($id, $providerName);
        $name = $provider->getName();

        $cacheKey = "dhunzo:music:details:{$name}:artist:{$id}";

        return Cache::remember($cacheKey, $this->detailsTtl, function () use ($provider, $id) {
            return $provider->getArtistDetails($id);
        });
    }

    /**
     * Get artist top songs.
     */
    public function getArtistSongs(string $id, ?string $providerName = null): array
    {
        $artist = $this->getArtist($id, $providerName);
        return $artist?->top_songs ?? [];
    }

    /**
     * Get playlist details with caching.
     */
    public function getPlaylist(string $id, ?string $providerName = null): ?PlaylistDTO
    {
        $provider = $this->resolver->resolveFromId($id, $providerName);
        $name = $provider->getName();

        $cacheKey = "dhunzo:music:details:{$name}:playlist:{$id}";

        return Cache::remember($cacheKey, $this->detailsTtl, function () use ($provider, $id) {
            return $provider->getPlaylistDetails($id);
        });
    }

    /**
     * Get playlist songs.
     */
    public function getPlaylistSongs(string $id, ?string $providerName = null): array
    {
        $playlist = $this->getPlaylist($id, $providerName);
        return $playlist?->songs ?? [];
    }

    /**
     * Get song recommendations.
     */
    public function getRecommendations(string $songId, ?string $providerName = null, int $limit = 10): array
    {
        $provider = $this->resolver->resolveFromId($songId, $providerName);
        return $provider->getRecommendations($songId, $limit);
    }

    /**
     * Create radio station.
     */
    public function createRadio(string $name, string $type = 'featured', ?string $providerName = null): ?string
    {
        $provider = $this->resolver->resolve($providerName);
        return $provider->createRadioStation($name, $type);
    }

    /**
     * Get radio song.
     */
    public function getRadioSong(string $stationId, ?string $providerName = null): ?SongDTO
    {
        $provider = $this->resolver->resolve($providerName);
        return $provider->getRadioSong($stationId);
    }

    /**
     * Get trending songs with caching.
     */
    public function getTrending(?string $providerName = null, int $limit = 20): array
    {
        $provider = $this->resolver->resolve($providerName);
        $name = $provider->getName();

        $cacheKey = "dhunzo:music:details:{$name}:trending:{$limit}";

        $songsData = Cache::remember($cacheKey, $this->searchTtl, function () use ($provider, $limit) {
            $songs = $provider->getTrending($limit);
            return array_map(fn(SongDTO $song) => $song->toArray(), $songs);
        });

        return array_map(fn(array $item) => SongDTO::fromArray($item), $songsData);
    }
}
