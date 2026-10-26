<?php

namespace App\Services\Lyrics;

use App\DTOs\Lyrics\LyricsDTO;
use App\Models\GeneratedLyric;
use App\Services\Lyrics\Ai\AiSpeechToTextService;
use App\Services\Music\MusicService;
use Illuminate\Support\Facades\Cache;

class LyricsService
{
    protected int $negativeCacheTtl;
    protected int $cacheTtl;

    public function __construct(
        protected LyricsResolver $resolver,
        protected MusicService $musicService,
        protected AiSpeechToTextService $aiSpeechToTextService
    ) {
        $this->negativeCacheTtl = (int) config('dhunzo.lyrics.circuit_breaker.negative_cache_ttl', 1800);
        $this->cacheTtl = 86400; // 24 hours
    }

    /**
     * Search lyrics by track details with positive, database and AI STT fallback caching.
     * Flow:
     * 1. Cache & DB Check
     * 2. Existing Lyrics Providers (LrcLib, BetterLyrics, KuGou, NetEase, Musixmatch, Genius, YouTubeCaption)
     * 3. AI Audio-to-Text Fallback (Only executed when existing providers return null)
     * 4. Save in Database & Return
     */
    public function search(
        string $title,
        ?string $artist = null,
        ?string $album = null,
        ?int $duration = null,
        ?string $audioUrl = null
    ): ?LyricsDTO {
        $hash = GeneratedLyric::computeHash($title, $artist);
        $cacheKey = "dhunzo:lyrics:search:{$hash}";
        $negativeKey = "dhunzo:lyrics:negative:{$hash}";

        // 1. Check in-memory / file cache
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            return $cached ? LyricsDTO::fromArray($cached) : null;
        }

        // 2. Check Database for previously generated AI lyrics or cached lyrics
        $dbLyric = GeneratedLyric::where('track_hash', $hash)->first();
        if ($dbLyric) {
            $dto = new LyricsDTO(
                id: "ai:{$dbLyric->track_hash}",
                provider: $dbLyric->provider,
                type: $dbLyric->type,
                lines: $dbLyric->lines ?? [],
                language: $dbLyric->language,
                raw: $dbLyric->raw_lrc,
                score: $dbLyric->confidence_score,
                extra: [
                    'source'       => 'database',
                    'language'     => $dbLyric->language,
                    'persisted_db' => true,
                ]
            );
            Cache::put($cacheKey, $dto->toArray(), $this->cacheTtl);
            return $dto;
        }

        // Check negative cache only for external provider calls
        if (!Cache::has($negativeKey)) {
            // 3. Query Existing Providers First
            $lyrics = $this->resolver->resolve($title, $artist, $album, $duration);

            if ($lyrics !== null) {
                Cache::put($cacheKey, $lyrics->toArray(), $this->cacheTtl);
                return $lyrics;
            }
        }

        // 4. Existing Providers Returned Null! Trigger AI Audio-to-Text Fallback
        if (config('dhunzo.lyrics.ai_fallback.enabled', true)) {
            $aiLyrics = $this->aiSpeechToTextService->generateFromAudio(
                title: $title,
                artist: $artist,
                album: $album,
                duration: $duration,
                audioUrl: $audioUrl
            );

            if ($aiLyrics !== null) {
                Cache::put($cacheKey, $aiLyrics->toArray(), $this->cacheTtl);
                return $aiLyrics;
            }
        }

        // Cache negative result
        Cache::put($negativeKey, true, $this->negativeCacheTtl);
        return null;
    }

    /**
     * Get lyrics by song ID. If song ID is provided, fetches song metadata to search lyrics.
     */
    public function getBySongId(string $songId): ?LyricsDTO
    {
        $cacheKey = "dhunzo:lyrics:song:{$songId}";
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            return $cached ? LyricsDTO::fromArray($cached) : null;
        }

        // Fetch song from MusicService
        $song = $this->musicService->getSong($songId);
        if (!$song) {
            return null;
        }

        $primaryArtist = !empty($song->artists[0]['name']) ? $song->artists[0]['name'] : null;
        $albumTitle = $song->album['title'] ?? null;
        $audioUrl = $song->playback['url'] ?? $song->playback['embed_url'] ?? null;

        $lyrics = $this->search($song->title, $primaryArtist, $albumTitle, $song->duration, $audioUrl);

        if ($lyrics) {
            Cache::put($cacheKey, $lyrics->toArray(), $this->cacheTtl);
        }

        return $lyrics;
    }

    /**
     * Get lyrics by provider-specific lyrics ID.
     */
    public function getById(string $id): ?LyricsDTO
    {
        if (str_starts_with($id, 'ai:')) {
            $hash = str_replace('ai:', '', $id);
            $dbLyric = GeneratedLyric::where('track_hash', $hash)->first();
            if ($dbLyric) {
                return new LyricsDTO(
                    id: "ai:{$dbLyric->track_hash}",
                    provider: $dbLyric->provider,
                    type: $dbLyric->type,
                    lines: $dbLyric->lines ?? [],
                    language: $dbLyric->language,
                    raw: $dbLyric->raw_lrc,
                    score: $dbLyric->confidence_score,
                    extra: [
                        'source'       => 'database',
                        'language'     => $dbLyric->language,
                        'persisted_db' => true,
                    ]
                );
            }
        }

        return $this->resolver->resolveById($id);
    }
}
