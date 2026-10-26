<?php

namespace App\Services\Lyrics\Providers;

use App\DTOs\Lyrics\LyricsDTO;
use App\Services\Lyrics\Contracts\LyricsProviderInterface;
use App\Services\Lyrics\LyricsMatcher;
use App\Services\Lyrics\LyricsNormalizer;
use Illuminate\Support\Facades\Http;
use Throwable;

class BetterLyricsProvider implements LyricsProviderInterface
{
    protected string $baseUrl;
    protected int $timeoutMs;

    public function __construct(
        protected LyricsNormalizer $normalizer,
        protected LyricsMatcher $matcher
    ) {
        $this->baseUrl = config('dhunzo.lyrics.providers.betterlyrics.base_url', 'https://lyrics.cx/api');
        $this->timeoutMs = (int) config('dhunzo.lyrics.timeout_ms', 2500);
    }

    public function getName(): string
    {
        return 'betterlyrics';
    }

    public function searchLyrics(string $title, ?string $artist = null, ?string $album = null, ?int $duration = null): ?LyricsDTO
    {
        try {
            $query = trim("{$title} {$artist}");
            $timeoutSec = (int) ceil($this->timeoutMs / 1000.0);

            $response = Http::timeout($timeoutSec)
                ->acceptJson()
                ->get("{$this->baseUrl}/search", [
                    'q'        => $query,
                    'duration' => $duration,
                ]);

            if (!$response->successful()) {
                return null;
            }

            $items = $response->json()['data'] ?? $response->json() ?? [];
            if (!is_array($items) || empty($items)) {
                return null;
            }

            // Find best matching candidate
            $bestCand = null;
            $bestScore = 0.0;

            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $score = $this->matcher->score(
                    queryTitle: $title,
                    queryArtist: $artist,
                    queryAlbum: $album,
                    queryDuration: $duration,
                    candTitle: $item['title'] ?? $item['name'] ?? '',
                    candArtist: $item['artist'] ?? null,
                    candAlbum: $item['album'] ?? null,
                    candDuration: isset($item['duration']) ? (int) $item['duration'] : null
                );

                if ($this->matcher->isMatch($score) && $score > $bestScore) {
                    $bestCand = $item;
                    $bestScore = $score;
                }
            }

            if (!$bestCand) {
                return null;
            }

            $raw = $bestCand['lyrics'] ?? $bestCand['syncedLyrics'] ?? $bestCand['lrc'] ?? '';
            if (empty($raw)) {
                return null;
            }

            $suggestedType = !empty($bestCand['synced']) || str_contains($raw, '[') ? 'line_synced' : 'plain';
            if (str_contains($raw, '<')) {
                $suggestedType = 'word_synced';
            }

            $normalized = $this->normalizer->normalize($raw, $suggestedType);

            return new LyricsDTO(
                id: 'betterlyrics:' . ($bestCand['id'] ?? md5($raw)),
                provider: 'betterlyrics',
                type: $normalized['type'],
                lines: $normalized['lines'],
                language: $bestCand['language'] ?? null,
                raw: $raw,
                score: $bestScore
            );
        } catch (Throwable) {
            return null;
        }
    }

    public function getLyricsById(string $id): ?LyricsDTO
    {
        $cleanId = str_starts_with($id, 'betterlyrics:') ? substr($id, 13) : $id;

        try {
            $timeoutSec = (int) ceil($this->timeoutMs / 1000.0);
            $response = Http::timeout($timeoutSec)
                ->get("{$this->baseUrl}/lyrics/{$cleanId}");

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            $raw = $data['lyrics'] ?? $data['lrc'] ?? '';
            $normalized = $this->normalizer->normalize($raw);

            return new LyricsDTO(
                id: 'betterlyrics:' . $cleanId,
                provider: 'betterlyrics',
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
