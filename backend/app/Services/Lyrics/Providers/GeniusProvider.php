<?php

namespace App\Services\Lyrics\Providers;

use App\DTOs\Lyrics\LyricsDTO;
use App\Services\Lyrics\Contracts\LyricsProviderInterface;
use App\Services\Lyrics\LyricsMatcher;
use App\Services\Lyrics\LyricsNormalizer;
use Illuminate\Support\Facades\Http;
use Throwable;

class GeniusProvider implements LyricsProviderInterface
{
    protected string $baseUrl;
    protected string $accessToken;
    protected int $timeoutMs;

    public function __construct(
        protected LyricsNormalizer $normalizer,
        protected LyricsMatcher $matcher
    ) {
        $this->baseUrl = config('dhunzo.lyrics.providers.genius.base_url', 'https://api.genius.com');
        $this->accessToken = (string) config('dhunzo.lyrics.providers.genius.access_token', '');
        $this->timeoutMs = (int) config('dhunzo.lyrics.timeout_ms', 2500);
    }

    public function getName(): string
    {
        return 'genius';
    }

    public function searchLyrics(string $title, ?string $artist = null, ?string $album = null, ?int $duration = null): ?LyricsDTO
    {
        try {
            $query = trim("{$title} {$artist}");
            $timeoutSec = (int) ceil($this->timeoutMs / 1000.0);

            $request = Http::timeout($timeoutSec);
            if (!empty($this->accessToken)) {
                $request = $request->withToken($this->accessToken);
            }

            $response = $request->get("{$this->baseUrl}/search", ['q' => $query]);

            if (!$response->successful()) {
                return null;
            }

            $hits = $response->json()['response']['hits'] ?? [];
            if (empty($hits)) {
                return null;
            }

            $bestHit = null;
            $bestScore = 0.0;

            foreach ($hits as $hit) {
                $result = $hit['result'] ?? [];
                $score = $this->matcher->score(
                    queryTitle: $title,
                    queryArtist: $artist,
                    queryAlbum: $album,
                    queryDuration: $duration,
                    candTitle: $result['title'] ?? '',
                    candArtist: $result['primary_artist']['name'] ?? null
                );

                if ($this->matcher->isMatch($score) && $score > $bestScore) {
                    $bestHit = $result;
                    $bestScore = $score;
                }
            }

            if (!$bestHit || empty($bestHit['url'])) {
                return null;
            }

            // Scrape lyrics container from song URL
            $pageResp = Http::timeout($timeoutSec)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get($bestHit['url']);

            if (!$pageResp->successful()) {
                return null;
            }

            $html = $pageResp->body();
            $lyricsText = $this->extractLyricsFromHtml($html);

            if (empty($lyricsText)) {
                return null;
            }

            $normalized = $this->normalizer->normalize($lyricsText, 'plain');

            return new LyricsDTO(
                id: 'genius:' . $bestHit['id'],
                provider: 'genius',
                type: 'plain',
                lines: $normalized['lines'],
                raw: $lyricsText,
                score: $bestScore
            );
        } catch (Throwable) {
            return null;
        }
    }

    public function getLyricsById(string $id): ?LyricsDTO
    {
        return null;
    }

    protected function extractLyricsFromHtml(string $html): string
    {
        // Genius uses data-lyrics-container="true" containers
        if (preg_match_all('/<div[^>]*data-lyrics-container="true"[^>]*>(.*?)<\/div>/s', $html, $matches)) {
            $combined = implode("\n", $matches[1]);
            // Convert <br> to newline
            $clean = preg_replace('/<br\s*\/?>/i', "\n", $combined);
            // Strip tags
            $clean = strip_tags($clean);
            return trim(html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return '';
    }
}
