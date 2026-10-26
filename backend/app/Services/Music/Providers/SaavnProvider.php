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
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

class SaavnProvider implements MusicProviderInterface
{
    protected string $baseUrl;
    protected float $timeout;
    protected array $defaultParams;

    public function __construct()
    {
        $this->baseUrl = config('dhunzo.music.providers.saavn.base_url', 'https://www.jiosaavn.com/api.php');
        $this->timeout = (float) config('dhunzo.music.providers.saavn.timeout', 5.0);
        $this->defaultParams = config('dhunzo.music.providers.saavn.default_params', [
            '_format'     => 'json',
            '_marker'     => '0',
            'api_version' => '4',
            'ctx'         => 'web6dot0',
        ]);
    }

    public function getName(): string
    {
        return 'saavn';
    }

    /**
     * Search for songs.
     *
     * @return array<int, SongDTO>
     */
    public function search(string $query, int $page = 1, int $limit = 20): array
    {
        $response = $this->request([
            '__call' => 'search.getResults',
            'q'      => $query,
            'p'      => $page,
            'n'      => $limit,
        ]);

        $results = $response['results'] ?? [];
        $songs = [];

        foreach ($results as $item) {
            $songs[] = $this->formatSong($item);
        }

        return $songs;
    }

    /**
     * Get song details by ID.
     */
    public function getSongDetails(string $id): ?SongDTO
    {
        $cleanId = $this->cleanId($id);

        $response = $this->request([
            '__call' => 'song.getDetails',
            'pids'   => $cleanId,
        ]);

        // Details response key is either $cleanId or first item in response
        $songData = $response[$cleanId] ?? (isset($response['songs'][0]) ? $response['songs'][0] : null);

        if (!$songData && is_array($response)) {
            $songData = reset($response);
        }

        if (empty($songData) || !is_array($songData) || empty($songData['id'])) {
            return null;
        }

        return $this->formatSong($songData);
    }

    /**
     * Get album details by ID.
     */
    public function getAlbumDetails(string $id): ?AlbumDTO
    {
        $cleanId = $this->cleanId($id);

        $response = $this->request([
            '__call'  => 'content.getAlbumDetails',
            'albumid' => $cleanId,
        ]);

        if (empty($response) || empty($response['id'] ?? $response['albumid'])) {
            return null;
        }

        $songs = [];
        foreach ($response['list'] ?? $response['songs'] ?? [] as $songItem) {
            $songs[] = $this->formatSong($songItem);
        }

        $artists = $this->extractArtists($response);

        return new AlbumDTO(
            id: 'saavn:' . ($response['id'] ?? $response['albumid'] ?? $cleanId),
            provider: 'saavn',
            provider_id: (string) ($response['id'] ?? $response['albumid'] ?? $cleanId),
            title: html_entity_decode($response['title'] ?? $response['name'] ?? ''),
            subtitle: html_entity_decode($response['subtitle'] ?? $response['header_desc'] ?? ''),
            artists: $artists,
            year: (string) ($response['year'] ?? ''),
            song_count: count($songs),
            artwork: $this->buildArtwork($response['image'] ?? null),
            songs: $songs,
            extra: [
                'language' => $response['language'] ?? null,
            ]
        );
    }

    /**
     * Get artist details by ID.
     */
    public function getArtistDetails(string $id): ?ArtistDTO
    {
        $cleanId = $this->cleanId($id);

        $response = $this->request([
            '__call'   => 'webapi.get',
            'type'     => 'artist',
            'artistId' => $cleanId,
        ]);

        if (empty($response) || empty($response['artistId'] ?? $response['id'] ?? $cleanId)) {
            return null;
        }

        $topSongs = [];
        foreach ($response['topSongs'] ?? [] as $songItem) {
            $topSongs[] = $this->formatSong($songItem);
        }

        $topAlbums = [];
        foreach ($response['topAlbums'] ?? [] as $albumItem) {
            $topAlbums[] = new AlbumDTO(
                id: 'saavn:' . ($albumItem['id'] ?? $albumItem['albumid'] ?? ''),
                provider: 'saavn',
                provider_id: (string) ($albumItem['id'] ?? $albumItem['albumid'] ?? ''),
                title: html_entity_decode($albumItem['title'] ?? $albumItem['name'] ?? ''),
                year: (string) ($albumItem['year'] ?? ''),
                artwork: $this->buildArtwork($albumItem['image'] ?? null)
            );
        }

        return new ArtistDTO(
            id: 'saavn:' . ($response['artistId'] ?? $response['id'] ?? $cleanId),
            provider: 'saavn',
            provider_id: (string) ($response['artistId'] ?? $response['id'] ?? $cleanId),
            name: html_entity_decode($response['name'] ?? ''),
            role: $response['role'] ?? 'Singer',
            image: $this->buildArtwork($response['image'] ?? null),
            bio: $response['bio'] ?? null,
            follower_count: isset($response['fan_count']) ? (int) $response['fan_count'] : null,
            top_songs: $topSongs,
            top_albums: $topAlbums,
            extra: [
                'is_verified' => (bool) ($response['is_verified'] ?? false),
            ]
        );
    }

    /**
     * Get playlist details by ID.
     */
    public function getPlaylistDetails(string $id): ?PlaylistDTO
    {
        $cleanId = $this->cleanId($id);

        $response = $this->request([
            '__call' => 'playlist.getDetails',
            'listid' => $cleanId,
        ]);

        if (empty($response) || empty($response['listid'] ?? $response['id'])) {
            return null;
        }

        $songs = [];
        foreach ($response['list'] ?? $response['songs'] ?? [] as $songItem) {
            $songs[] = $this->formatSong($songItem);
        }

        return new PlaylistDTO(
            id: 'saavn:' . ($response['listid'] ?? $response['id'] ?? $cleanId),
            provider: 'saavn',
            provider_id: (string) ($response['listid'] ?? $response['id'] ?? $cleanId),
            title: html_entity_decode($response['title'] ?? $response['listname'] ?? ''),
            subtitle: html_entity_decode($response['subtitle'] ?? $response['header_desc'] ?? ''),
            description: html_entity_decode($response['description'] ?? ''),
            follower_count: isset($response['fan_count']) ? (int) $response['fan_count'] : null,
            song_count: count($songs),
            artwork: $this->buildArtwork($response['image'] ?? null),
            songs: $songs
        );
    }

    /**
     * Get recommendations for a song.
     *
     * @return array<int, SongDTO>
     */
    public function getRecommendations(string $songId, int $limit = 10): array
    {
        $cleanId = $this->cleanId($songId);

        $response = $this->request([
            '__call' => 'reco.getreco',
            'pid'    => $cleanId,
        ]);

        $songs = [];
        if (is_array($response)) {
            foreach (array_slice($response, 0, $limit) as $item) {
                if (is_array($item) && !empty($item['id'])) {
                    $songs[] = $this->formatSong($item);
                }
            }
        }

        return $songs;
    }

    /**
     * Create a radio station (featured or artist).
     */
    public function createRadioStation(string $name, string $type = 'featured'): ?string
    {
        $call = ($type === 'artist')
            ? 'webradio.createArtistStation'
            : 'webradio.createFeaturedStation';

        $paramKey = ($type === 'artist') ? 'artist_id' : 'name';

        $response = $this->request([
            '__call'  => $call,
            $paramKey => $name,
        ]);

        return $response['stationid'] ?? null;
    }

    /**
     * Get next song from radio station.
     */
    public function getRadioSong(string $stationId): ?SongDTO
    {
        $response = $this->request([
            '__call'    => 'webradio.getSong',
            'stationid' => $stationId,
            'k'         => 1,
        ]);

        $songData = $response['song'] ?? (isset($response[0]) ? $response[0] : null);

        if (empty($songData) || !is_array($songData) || empty($songData['id'])) {
            return null;
        }

        return $this->formatSong($songData);
    }

    /**
     * Get trending / top search songs.
     *
     * @return array<int, SongDTO>
     */
    public function getTrending(int $limit = 20): array
    {
        $response = $this->request([
            '__call' => 'content.getTopSearches',
        ]);

        $songs = [];
        if (is_array($response)) {
            foreach (array_slice($response, 0, $limit) as $item) {
                if (is_array($item) && !empty($item['id'])) {
                    $songs[] = $this->formatSong($item);
                }
            }
        }

        return $songs;
    }

    /**
     * Send HTTP request to Saavn API and sanitize response.
     */
    public function request(array $params): mixed
    {
        $queryParams = array_merge($this->defaultParams, $params);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept'     => 'application/json',
                ])
                ->get($this->baseUrl, $queryParams);

            if ($response->serverError()) {
                throw new ProviderUnavailableException('JioSaavn server error: ' . $response->status(), 'saavn');
            }

            if ($response->status() === 429) {
                throw new ProviderUnavailableException('JioSaavn rate limit reached.', 'saavn', null, 429);
            }

            if (!$response->successful()) {
                throw new ProviderException('JioSaavn request failed: ' . $response->status(), 'saavn', null, $response->status());
            }

            return $this->sanitizeAndDecode($response->body());
        } catch (ConnectionException $e) {
            throw new ProviderTimeoutException('JioSaavn connection timeout: ' . $e->getMessage(), 'saavn', null, 504, $e);
        } catch (RequestException $e) {
            throw new ProviderException('JioSaavn HTTP error: ' . $e->getMessage(), 'saavn', null, 502, $e);
        } catch (ProviderException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ProviderException('JioSaavn unexpected error: ' . $e->getMessage(), 'saavn', null, 502, $e);
        }
    }

    /**
     * Sanitize non-standard output wrappers returned by Saavn endpoints before decoding.
     */
    public function sanitizeAndDecode(string $body): mixed
    {
        // Strip UTF-8 BOM if present
        $body = preg_replace('/^\xEF\xBB\xBF/', '', $body);
        $body = trim($body);

        // Strip HTML/script wrapper if present
        if (!str_starts_with($body, '{') && !str_starts_with($body, '[')) {
            if (preg_match('/(\{.*\}|\[.*\])/s', $body, $matches)) {
                $body = $matches[1];
            }
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Format a raw Saavn song payload into standard SongDTO.
     */
    public function formatSong(array $item): SongDTO
    {
        $id = (string) ($item['id'] ?? '');
        $title = html_entity_decode($item['title'] ?? $item['song'] ?? '');
        $subtitle = html_entity_decode($item['subtitle'] ?? '');

        // Extract album info
        $albumId = (string) ($item['more_info']['album_id'] ?? $item['albumid'] ?? '');
        $albumTitle = html_entity_decode($item['more_info']['album'] ?? $item['album'] ?? '');

        // Extract artists
        $artists = $this->extractArtists($item);

        // Extract duration
        $duration = isset($item['more_info']['duration'])
            ? (int) $item['more_info']['duration']
            : (isset($item['duration']) ? (int) $item['duration'] : null);

        // Extract year and language
        $year = isset($item['year']) ? (string) $item['year'] : null;
        $language = $item['language'] ?? null;

        // Artwork scaling
        $artwork = $this->buildArtwork($item['image'] ?? null);

        // Playback resolution
        $playbackUrl = $this->resolvePlaybackUrl($item);

        return new SongDTO(
            id: 'saavn:' . $id,
            provider: 'saavn',
            provider_id: $id,
            title: $title,
            subtitle: $subtitle ?: null,
            artists: $artists,
            album: [
                'id'    => $albumId ?: null,
                'title' => $albumTitle ?: null,
            ],
            duration: $duration,
            language: $language,
            year: $year,
            artwork: $artwork,
            playback: [
                'type'      => 'stream',
                'url'       => $playbackUrl,
                'embed_url' => null,
            ],
            extra: [
                'has_lyrics' => ($item['more_info']['has_lyrics'] ?? $item['has_lyrics'] ?? 'false') === 'true',
            ]
        );
    }

    /**
     * Artwork image scaling: standardizes 50x50, 150x150, and 500x500 high-res assets.
     */
    public function buildArtwork(?string $imageUrl): array
    {
        if (empty($imageUrl)) {
            return ['small' => null, 'medium' => null, 'large' => null];
        }

        return [
            'small'  => preg_replace('/(50x50|150x150|500x500)/', '50x50', $imageUrl),
            'medium' => preg_replace('/(50x50|150x150|500x500)/', '150x150', $imageUrl),
            'large'  => preg_replace('/(50x50|150x150|500x500)/', '500x500', $imageUrl),
        ];
    }

    /**
     * Resolve playable stream audio URL from media preview URL or encrypted media URL.
     */
    protected function resolvePlaybackUrl(array $item): ?string
    {
        $previewUrl = $item['more_info']['media_preview_url'] ?? $item['media_preview_url'] ?? null;

        if (!empty($previewUrl)) {
            // High quality stream reconstruction from preview URL
            $streamUrl = str_replace(['preview.saavncdn.com', '_96_p.mp4'], ['aac.saavncdn.com', '_320.mp4'], $previewUrl);
            return $streamUrl;
        }

        $encryptedUrl = $item['more_info']['encrypted_media_url'] ?? $item['encrypted_media_url'] ?? null;
        if (!empty($encryptedUrl)) {
            $decrypted = $this->decryptMediaUrl($encryptedUrl);
            if ($decrypted) {
                return $decrypted;
            }
        }

        return null;
    }

    /**
     * Decrypt encrypted media URL using Saavn DES key.
     */
    protected function decryptMediaUrl(string $encrypted): ?string
    {
        try {
            $key = '38346591';
            $ciphertext = base64_decode($encrypted);
            if ($ciphertext === false) {
                return null;
            }

            // In OpenSSL, try available DES ciphers
            foreach (['des-ede-ecb', 'des-ede3-ecb', 'DES-ECB'] as $cipher) {
                if (in_array(strtolower($cipher), array_map('strtolower', openssl_get_cipher_methods()))) {
                    $decrypted = openssl_decrypt($ciphertext, $cipher, str_pad($key, 24, "\0"), OPENSSL_RAW_DATA);
                    if ($decrypted && filter_var($decrypted, FILTER_VALIDATE_URL)) {
                        return $decrypted;
                    }
                }
            }
        } catch (Throwable) {
            // Graceful fallback
        }

        return null;
    }

    /**
     * Extract artists into structured array from Saavn payload.
     */
    protected function extractArtists(array $item): array
    {
        $artists = [];

        // Try artistMap first
        $artistMap = $item['more_info']['artistMap']['artists'] ?? [];
        if (!empty($artistMap) && is_array($artistMap)) {
            foreach ($artistMap as $artist) {
                $artists[] = [
                    'id'   => isset($artist['id']) ? (string) $artist['id'] : null,
                    'name' => html_entity_decode($artist['name'] ?? ''),
                    'role' => $artist['role'] ?? 'singer',
                ];
            }
            return $artists;
        }

        // Try primary_artists / music / singers strings
        $primaryStr = $item['more_info']['music'] ?? $item['primary_artists'] ?? $item['singers'] ?? $item['artist'] ?? '';
        if (!empty($primaryStr)) {
            $names = array_map('trim', explode(',', $primaryStr));
            foreach ($names as $name) {
                if (!empty($name)) {
                    $artists[] = [
                        'id'   => null,
                        'name' => html_entity_decode($name),
                        'role' => 'singer',
                    ];
                }
            }
        }

        return $artists;
    }

    /**
     * Strip 'saavn:' provider prefix if present.
     */
    protected function cleanId(string $id): string
    {
        if (str_starts_with($id, 'saavn:')) {
            return substr($id, 6);
        }

        return $id;
    }
}
