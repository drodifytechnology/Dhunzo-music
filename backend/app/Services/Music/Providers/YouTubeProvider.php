<?php

namespace App\Services\Music\Providers;

use App\DTOs\Music\AlbumDTO;
use App\DTOs\Music\ArtistDTO;
use App\DTOs\Music\PlaylistDTO;
use App\DTOs\Music\SongDTO;
use App\Exceptions\ProviderException;
use App\Exceptions\ProviderTimeoutException;
use App\Exceptions\ProviderUnavailableException;
use App\Services\Music\Contracts\MusicProviderInterface;
use DateInterval;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

class YouTubeProvider implements MusicProviderInterface
{
    protected string $baseUrl;
    protected string $apiKey;
    protected float $timeout;

    public function __construct()
    {
        $this->baseUrl = config('dhunzo.music.providers.youtube.base_url', 'https://www.googleapis.com/youtube/v3');
        $this->apiKey = (string) config('dhunzo.music.providers.youtube.api_key', '');
        $this->timeout = (float) config('dhunzo.music.providers.youtube.timeout', 5.0);
    }

    public function getName(): string
    {
        return 'youtube';
    }

    /**
     * Search YouTube videos filtered by music category.
     *
     * @return array<int, SongDTO>
     */
    public function search(string $query, int $page = 1, int $limit = 20): array
    {
        $params = [
            'part'            => 'snippet',
            'q'               => $query,
            'type'            => 'video',
            'videoCategoryId' => '10', // Music
            'maxResults'      => min($limit, 50),
        ];

        $data = $this->request('/search', $params);
        $items = $data['items'] ?? [];

        // Collect video IDs to fetch duration from /videos
        $videoIds = [];
        foreach ($items as $item) {
            if (isset($item['id']['videoId'])) {
                $videoIds[] = $item['id']['videoId'];
            }
        }

        $durations = [];
        if (!empty($videoIds)) {
            $durations = $this->fetchDurations($videoIds);
        }

        $songs = [];
        foreach ($items as $item) {
            $videoId = $item['id']['videoId'] ?? null;
            if ($videoId) {
                $duration = $durations[$videoId] ?? null;
                $songs[] = $this->formatVideoItem($item, $videoId, $duration);
            }
        }

        return $songs;
    }

    /**
     * Get details of a single YouTube video.
     */
    public function getSongDetails(string $id): ?SongDTO
    {
        $cleanId = $this->cleanId($id);

        $data = $this->request('/videos', [
            'part' => 'snippet,contentDetails',
            'id'   => $cleanId,
        ]);

        $item = $data['items'][0] ?? null;
        if (!$item) {
            return null;
        }

        $duration = null;
        if (isset($item['contentDetails']['duration'])) {
            $duration = $this->parseIsoDuration($item['contentDetails']['duration']);
        }

        return $this->formatVideoItem($item, $cleanId, $duration);
    }

    /**
     * YouTube albums fallback.
     */
    public function getAlbumDetails(string $id): ?AlbumDTO
    {
        return null;
    }

    /**
     * YouTube channel details as artist.
     */
    public function getArtistDetails(string $id): ?ArtistDTO
    {
        $cleanId = $this->cleanId($id);

        $data = $this->request('/channels', [
            'part' => 'snippet,statistics',
            'id'   => $cleanId,
        ]);

        $item = $data['items'][0] ?? null;
        if (!$item) {
            return null;
        }

        $snippet = $item['snippet'] ?? [];
        $stats = $item['statistics'] ?? [];

        return new ArtistDTO(
            id: 'youtube:' . $cleanId,
            provider: 'youtube',
            provider_id: $cleanId,
            name: $snippet['title'] ?? '',
            role: 'Channel',
            image: [
                'small'  => $snippet['thumbnails']['default']['url'] ?? null,
                'medium' => $snippet['thumbnails']['medium']['url'] ?? null,
                'large'  => $snippet['thumbnails']['high']['url'] ?? null,
            ],
            bio: $snippet['description'] ?? null,
            follower_count: isset($stats['subscriberCount']) ? (int) $stats['subscriberCount'] : null
        );
    }

    /**
     * YouTube playlist details.
     */
    public function getPlaylistDetails(string $id): ?PlaylistDTO
    {
        $cleanId = $this->cleanId($id);

        $playlistData = $this->request('/playlists', [
            'part' => 'snippet',
            'id'   => $cleanId,
        ]);

        $playlistItem = $playlistData['items'][0] ?? null;
        if (!$playlistItem) {
            return null;
        }

        $itemsData = $this->request('/playlistItems', [
            'part'       => 'snippet',
            'playlistId' => $cleanId,
            'maxResults' => 50,
        ]);

        $songs = [];
        foreach ($itemsData['items'] ?? [] as $item) {
            $videoId = $item['snippet']['resourceId']['videoId'] ?? null;
            if ($videoId) {
                $songs[] = $this->formatVideoItem($item, $videoId);
            }
        }

        $snippet = $playlistItem['snippet'] ?? [];

        return new PlaylistDTO(
            id: 'youtube:' . $cleanId,
            provider: 'youtube',
            provider_id: $cleanId,
            title: $snippet['title'] ?? '',
            subtitle: $snippet['channelTitle'] ?? null,
            description: $snippet['description'] ?? null,
            song_count: count($songs),
            artwork: [
                'small'  => $snippet['thumbnails']['default']['url'] ?? null,
                'medium' => $snippet['thumbnails']['medium']['url'] ?? null,
                'large'  => $snippet['thumbnails']['high']['url'] ?? null,
            ],
            songs: $songs
        );
    }

    /**
     * Recommendations via related search.
     *
     * @return array<int, SongDTO>
     */
    public function getRecommendations(string $songId, int $limit = 10): array
    {
        $cleanId = $this->cleanId($songId);

        // Fetch title of video first to perform related query
        $song = $this->getSongDetails($cleanId);
        if (!$song) {
            return [];
        }

        return $this->search($song->title, 1, $limit);
    }

    public function createRadioStation(string $name, string $type = 'featured'): ?string
    {
        return null;
    }

    public function getRadioSong(string $stationId): ?SongDTO
    {
        return null;
    }

    /**
     * Trending music videos.
     *
     * @return array<int, SongDTO>
     */
    public function getTrending(int $limit = 20): array
    {
        $data = $this->request('/videos', [
            'part'            => 'snippet,contentDetails',
            'chart'           => 'mostPopular',
            'videoCategoryId' => '10', // Music
            'maxResults'      => min($limit, 50),
        ]);

        $songs = [];
        foreach ($data['items'] ?? [] as $item) {
            $videoId = $item['id'] ?? null;
            if ($videoId) {
                $duration = isset($item['contentDetails']['duration'])
                    ? $this->parseIsoDuration($item['contentDetails']['duration'])
                    : null;
                $songs[] = $this->formatVideoItem($item, $videoId, $duration);
            }
        }

        return $songs;
    }

    /**
     * Send HTTP request to Google YouTube Data API.
     */
    public function request(string $endpoint, array $params = []): array
    {
        $params['key'] = $this->apiKey;

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->get($this->baseUrl . $endpoint, $params);

            if ($response->status() === 403) {
                throw new ProviderUnavailableException('YouTube API quota exceeded or key forbidden.', 'youtube', null, 403);
            }

            if ($response->serverError()) {
                throw new ProviderUnavailableException('YouTube API server error: ' . $response->status(), 'youtube');
            }

            if (!$response->successful()) {
                throw new ProviderException('YouTube API request failed: ' . $response->status(), 'youtube', $response->json(), $response->status());
            }

            return $response->json() ?? [];
        } catch (ConnectionException $e) {
            throw new ProviderTimeoutException('YouTube API connection timeout.', 'youtube', null, 504, $e);
        } catch (RequestException $e) {
            throw new ProviderException('YouTube API HTTP error: ' . $e->getMessage(), 'youtube', null, 502, $e);
        } catch (ProviderException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ProviderException('YouTube API unexpected error: ' . $e->getMessage(), 'youtube', null, 502, $e);
        }
    }

    /**
     * Fetch durations for multiple video IDs.
     *
     * @param array<string> $videoIds
     * @return array<string, int>
     */
    protected function fetchDurations(array $videoIds): array
    {
        try {
            $data = $this->request('/videos', [
                'part' => 'contentDetails',
                'id'   => implode(',', $videoIds),
            ]);

            $durations = [];
            foreach ($data['items'] ?? [] as $item) {
                $vid = $item['id'] ?? null;
                $iso = $item['contentDetails']['duration'] ?? null;
                if ($vid && $iso) {
                    $durations[$vid] = $this->parseIsoDuration($iso);
                }
            }

            return $durations;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Format a YouTube video item into standardized SongDTO.
     */
    public function formatVideoItem(array $item, string $videoId, ?int $duration = null): SongDTO
    {
        $snippet = $item['snippet'] ?? [];
        $title = html_entity_decode($snippet['title'] ?? '');
        $channelTitle = html_entity_decode($snippet['channelTitle'] ?? '');

        // Extract published year
        $year = null;
        if (!empty($snippet['publishedAt'])) {
            $year = substr($snippet['publishedAt'], 0, 4);
        }

        // Thumbnails
        $thumbnails = $snippet['thumbnails'] ?? [];
        $artwork = [
            'small'  => $thumbnails['default']['url'] ?? null,
            'medium' => $thumbnails['medium']['url'] ?? null,
            'large'  => $thumbnails['maxres']['url'] ?? $thumbnails['high']['url'] ?? $thumbnails['medium']['url'] ?? null,
        ];

        return new SongDTO(
            id: 'youtube:' . $videoId,
            provider: 'youtube',
            provider_id: $videoId,
            title: $title,
            subtitle: $channelTitle ?: null,
            artists: [
                [
                    'id'   => $snippet['channelId'] ?? null,
                    'name' => $channelTitle,
                    'role' => 'uploader',
                ],
            ],
            album: [
                'id'    => null,
                'title' => null,
            ],
            duration: $duration,
            language: null,
            year: $year,
            artwork: $artwork,
            playback: [
                'type'      => 'provider_reference',
                'url'       => 'https://www.youtube.com/watch?v=' . $videoId,
                'embed_url' => 'https://www.youtube.com/embed/' . $videoId,
            ]
        );
    }

    /**
     * Parse ISO 8601 duration (e.g. PT4M13S) into seconds.
     */
    protected function parseIsoDuration(string $duration): int
    {
        try {
            $interval = new DateInterval($duration);
            return ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * Strip 'youtube:' provider prefix if present.
     */
    protected function cleanId(string $id): string
    {
        if (str_starts_with($id, 'youtube:')) {
            return substr($id, 8);
        }

        return $id;
    }
}
