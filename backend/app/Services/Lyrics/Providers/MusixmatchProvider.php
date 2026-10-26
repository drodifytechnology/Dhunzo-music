<?php

namespace App\Services\Lyrics\Providers;

use App\DTOs\Lyrics\LyricsDTO;
use App\Services\Lyrics\Contracts\LyricsProviderInterface;
use App\Services\Lyrics\LyricsMatcher;
use App\Services\Lyrics\LyricsNormalizer;
use Illuminate\Support\Facades\Http;
use Throwable;

class MusixmatchProvider implements LyricsProviderInterface
{
    protected string $baseUrl;
    protected string $token;
    protected int $timeoutMs;

    public function __construct(
        protected LyricsNormalizer $normalizer,
        protected LyricsMatcher $matcher
    ) {
        $this->baseUrl = config('dhunzo.lyrics.providers.musixmatch.base_url', 'https://apic-desktop.musixmatch.com/ws/1.1');
        $this->token = (string) config('dhunzo.lyrics.providers.musixmatch.token', '');
        $this->timeoutMs = (int) config('dhunzo.lyrics.timeout_ms', 2500);
    }

    public function getName(): string
    {
        return 'musixmatch';
    }

    public function searchLyrics(string $title, ?string $artist = null, ?string $album = null, ?int $duration = null): ?LyricsDTO
    {
        try {
            $timeoutSec = (int) ceil($this->timeoutMs / 1000.0);

            $params = [
                'format'    => 'json',
                'q_track'   => $title,
                'q_artist'  => $artist ?? '',
                'usertoken' => $this->token,
                'app_id'    => 'web-desktop-app-v1.0',
            ];

            if ($duration) {
                $params['f_subtitle_length'] = $duration;
            }

            $response = Http::timeout($timeoutSec)
                ->withHeaders([
                    'Cookie' => 'AWSELBCORS=0; AWSELB=0;',
                ])
                ->get("{$this->baseUrl}/macro.subtitles.get", $params);

            if (!$response->successful()) {
                return null;
            }

            $body = $response->json()['message']['body']['macro_calls'] ?? [];

            // Verify matched track name and artist match the query to avoid mismatched lyrics
            $matchedTrack = $body['matcher.track.get']['message']['body']['track'] ?? null;
            if ($matchedTrack) {
                $candTitle = $matchedTrack['track_name'] ?? '';
                $candArtist = $matchedTrack['artist_name'] ?? null;
                $candAlbum = $matchedTrack['album_name'] ?? null;
                $candDuration = isset($matchedTrack['track_length']) ? (int) $matchedTrack['track_length'] : null;

                $score = $this->matcher->score(
                    queryTitle: $title,
                    queryArtist: $artist,
                    queryAlbum: $album,
                    queryDuration: $duration,
                    candTitle: $candTitle,
                    candArtist: $candArtist,
                    candAlbum: $candAlbum,
                    candDuration: $candDuration
                );

                if (!$this->matcher->isMatch($score)) {
                    return null;
                }
            }

            $subtitles = $body['track.subtitles.get']['message']['body']['subtitle_list'][0]['subtitle'] ?? null;

            if (!empty($subtitles['subtitle_body'])) {
                $raw = $subtitles['subtitle_body'];
                $normalized = $this->normalizer->normalize($raw, 'line_synced');

                return new LyricsDTO(
                    id: 'musixmatch:' . ($subtitles['subtitle_id'] ?? md5($raw)),
                    provider: 'musixmatch',
                    type: $normalized['type'],
                    lines: $normalized['lines'],
                    language: $subtitles['subtitle_language'] ?? null,
                    raw: $raw,
                    score: 0.95
                );
            }

            // Fallback to track.lyrics.get
            $lyrics = $body['track.lyrics.get']['message']['body']['lyrics'] ?? null;
            if (!empty($lyrics['lyrics_body'])) {
                $raw = $lyrics['lyrics_body'];
                $normalized = $this->normalizer->normalize($raw, 'plain');

                return new LyricsDTO(
                    id: 'musixmatch:' . ($lyrics['lyrics_id'] ?? md5($raw)),
                    provider: 'musixmatch',
                    type: 'plain',
                    lines: $normalized['lines'],
                    language: $lyrics['lyrics_language'] ?? null,
                    raw: $raw,
                    score: 0.85
                );
            }

            return null;
        } catch (Throwable) {
            return null;
        }
    }

    public function getLyricsById(string $id): ?LyricsDTO
    {
        return null;
    }
}
