<?php

namespace App\Services\Lyrics\Providers;

use App\DTOs\Lyrics\LyricsDTO;
use App\Services\Lyrics\Contracts\LyricsProviderInterface;
use App\Services\Lyrics\LyricsMatcher;
use App\Services\Lyrics\LyricsNormalizer;
use Illuminate\Support\Facades\Http;
use Throwable;

class LrcLibProvider implements LyricsProviderInterface
{
    protected string $baseUrl;
    protected int $timeoutMs;

    public function __construct(
        protected LyricsNormalizer $normalizer,
        protected LyricsMatcher $matcher
    ) {
        $this->baseUrl = config('dhunzo.lyrics.providers.lrclib.base_url', 'https://lrclib.net/api');
        $this->timeoutMs = (int) config('dhunzo.lyrics.timeout_ms', 2500);
    }

    public function getName(): string
    {
        return 'lrclib';
    }

    public function searchLyrics(string $title, ?string $artist = null, ?string $album = null, ?int $duration = null): ?LyricsDTO
    {
        try {
            $params = [
                'track_name' => $title,
            ];
            if ($artist) {
                $params['artist_name'] = $artist;
            }
            if ($album) {
                $params['album_name'] = $album;
            }
            if ($duration) {
                $params['duration'] = $duration;
            }

            $timeoutSec = (int) ceil($this->timeoutMs / 1000.0);
            $response = Http::timeout($timeoutSec)
                ->withHeaders([
                    'User-Agent' => 'DhunzoMusic/1.0 (https://github.com/dhunzo)',
                ])
                ->get("{$this->baseUrl}/get", $params);

            $data = $response->json();

            // If direct match failed, try /search?q=
            if (!$response->successful() || empty($data['syncedLyrics'] ?? $data['plainLyrics'])) {
                $searchQuery = trim("{$title} {$artist}");
                $searchResp = Http::timeout($timeoutSec)
                    ->withHeaders(['User-Agent' => 'DhunzoMusic/1.0'])
                    ->get("{$this->baseUrl}/search", ['q' => $searchQuery]);

                if ($searchResp->successful()) {
                    $candidates = $searchResp->json() ?? [];
                    $bestCandidate = null;
                    $bestScore = 0.0;

                    foreach ($candidates as $cand) {
                        $score = $this->matcher->score(
                            queryTitle: $title,
                            queryArtist: $artist,
                            queryAlbum: $album,
                            queryDuration: $duration,
                            candTitle: $cand['trackName'] ?? '',
                            candArtist: $cand['artistName'] ?? null,
                            candAlbum: $cand['albumName'] ?? null,
                            candDuration: isset($cand['duration']) ? (int) $cand['duration'] : null
                        );

                        if ($this->matcher->isMatch($score) && $score > $bestScore) {
                            $bestCandidate = $cand;
                            $bestScore = $score;
                        }
                    }

                    if ($bestCandidate) {
                        $data = $bestCandidate;
                        $data['_score'] = $bestScore;
                    }
                }
            }

            if (empty($data) || empty($data['syncedLyrics'] ?? $data['plainLyrics'])) {
                return null;
            }

            $raw = !empty($data['syncedLyrics']) ? $data['syncedLyrics'] : $data['plainLyrics'];
            $suggestedType = !empty($data['syncedLyrics']) ? 'line_synced' : 'plain';
            $normalized = $this->normalizer->normalize($raw, $suggestedType);

            return new LyricsDTO(
                id: 'lrclib:' . ($data['id'] ?? md5($title . $artist)),
                provider: 'lrclib',
                type: $normalized['type'],
                lines: $normalized['lines'],
                language: null,
                raw: $raw,
                score: $data['_score'] ?? 1.0,
                extra: [
                    'duration' => $data['duration'] ?? null,
                    'isrc'     => $data['isrc'] ?? null,
                ]
            );
        } catch (Throwable) {
            return null;
        }
    }

    public function getLyricsById(string $id): ?LyricsDTO
    {
        $cleanId = str_starts_with($id, 'lrclib:') ? substr($id, 7) : $id;

        try {
            $timeoutSec = (int) ceil($this->timeoutMs / 1000.0);
            $response = Http::timeout($timeoutSec)
                ->withHeaders(['User-Agent' => 'DhunzoMusic/1.0'])
                ->get("{$this->baseUrl}/get/{$cleanId}");

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            $raw = !empty($data['syncedLyrics']) ? $data['syncedLyrics'] : ($data['plainLyrics'] ?? '');
            $suggestedType = !empty($data['syncedLyrics']) ? 'line_synced' : 'plain';
            $normalized = $this->normalizer->normalize($raw, $suggestedType);

            return new LyricsDTO(
                id: 'lrclib:' . $cleanId,
                provider: 'lrclib',
                type: $normalized['type'],
                lines: $normalized['lines'],
                raw: $raw,
                score: 1.0
            );
        } catch (Throwable) {
            return null;
        }
    }
}
