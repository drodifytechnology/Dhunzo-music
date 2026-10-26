<?php

namespace App\Services\Lyrics\Providers;

use App\DTOs\Lyrics\LyricsDTO;
use App\Services\Lyrics\Contracts\LyricsProviderInterface;
use App\Services\Lyrics\LyricsMatcher;
use App\Services\Lyrics\LyricsNormalizer;
use Illuminate\Support\Facades\Http;
use Throwable;

class NetEaseProvider implements LyricsProviderInterface
{
    protected string $searchUrl;
    protected string $lyricsUrl;
    protected int $timeoutMs;

    public function __construct(
        protected LyricsNormalizer $normalizer,
        protected LyricsMatcher $matcher
    ) {
        $this->searchUrl = config('dhunzo.lyrics.providers.netease.search_url', 'https://music.163.com/api/search/get/web');
        $this->lyricsUrl = config('dhunzo.lyrics.providers.netease.lyrics_url', 'https://music.163.com/api/song/lyric');
        $this->timeoutMs = (int) config('dhunzo.lyrics.timeout_ms', 2500);
    }

    public function getName(): string
    {
        return 'netease';
    }

    public function searchLyrics(string $title, ?string $artist = null, ?string $album = null, ?int $duration = null): ?LyricsDTO
    {
        try {
            $query = trim("{$title} {$artist}");
            $timeoutSec = (int) ceil($this->timeoutMs / 1000.0);

            $searchResp = Http::timeout($timeoutSec)
                ->withHeaders([
                    'Referer' => 'https://music.163.com',
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                ])
                ->asForm()
                ->post($this->searchUrl, [
                    's'      => $query,
                    'type'   => 1,
                    'offset' => 0,
                    'limit'  => 5,
                ]);

            if (!$searchResp->successful()) {
                return null;
            }

            $songs = $searchResp->json()['result']['songs'] ?? [];
            if (empty($songs) || !is_array($songs)) {
                return null;
            }

            $bestSong = null;
            $bestScore = 0.0;

            foreach ($songs as $song) {
                $candArtist = isset($song['artists'][0]['name']) ? $song['artists'][0]['name'] : null;
                $candAlbum = isset($song['album']['name']) ? $song['album']['name'] : null;
                $candDuration = isset($song['duration']) ? (int) round($song['duration'] / 1000) : null;

                $score = $this->matcher->score(
                    queryTitle: $title,
                    queryArtist: $artist,
                    queryAlbum: $album,
                    queryDuration: $duration,
                    candTitle: $song['name'] ?? '',
                    candArtist: $candArtist,
                    candAlbum: $candAlbum,
                    candDuration: $candDuration
                );

                if ($this->matcher->isMatch($score) && $score > $bestScore) {
                    $bestSong = $song;
                    $bestScore = $score;
                }
            }

            if (!$bestSong || empty($bestSong['id'])) {
                return null;
            }

            // Fetch lyric
            $lyricResp = Http::timeout($timeoutSec)
                ->withHeaders(['Referer' => 'https://music.163.com'])
                ->get($this->lyricsUrl, [
                    'os' => 'pc',
                    'id' => $bestSong['id'],
                    'lv' => -1,
                    'kv' => -1,
                    'tv' => -1,
                ]);

            if (!$lyricResp->successful()) {
                return null;
            }

            $raw = $lyricResp->json()['lrc']['lyric'] ?? '';
            if (empty($raw)) {
                return null;
            }

            $normalized = $this->normalizer->normalize($raw, 'line_synced');

            return new LyricsDTO(
                id: 'netease:' . $bestSong['id'],
                provider: 'netease',
                type: $normalized['type'],
                lines: $normalized['lines'],
                raw: $raw,
                score: $bestScore
            );
        } catch (Throwable) {
            return null;
        }
    }

    public function getLyricsById(string $id): ?LyricsDTO
    {
        $cleanId = str_starts_with($id, 'netease:') ? substr($id, 8) : $id;

        try {
            $timeoutSec = (int) ceil($this->timeoutMs / 1000.0);
            $resp = Http::timeout($timeoutSec)
                ->withHeaders(['Referer' => 'https://music.163.com'])
                ->get($this->lyricsUrl, [
                    'os' => 'pc',
                    'id' => $cleanId,
                    'lv' => -1,
                ]);

            if (!$resp->successful()) {
                return null;
            }

            $raw = $resp->json()['lrc']['lyric'] ?? '';
            $normalized = $this->normalizer->normalize($raw, 'line_synced');

            return new LyricsDTO(
                id: 'netease:' . $cleanId,
                provider: 'netease',
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
