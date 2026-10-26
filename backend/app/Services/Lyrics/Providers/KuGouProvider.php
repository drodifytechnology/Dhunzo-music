<?php

namespace App\Services\Lyrics\Providers;

use App\DTOs\Lyrics\LyricsDTO;
use App\Services\Lyrics\Contracts\LyricsProviderInterface;
use App\Services\Lyrics\LyricsMatcher;
use App\Services\Lyrics\LyricsNormalizer;
use Illuminate\Support\Facades\Http;
use Throwable;

class KuGouProvider implements LyricsProviderInterface
{
    protected string $searchUrl;
    protected string $downloadUrl;
    protected int $timeoutMs;

    public function __construct(
        protected LyricsNormalizer $normalizer,
        protected LyricsMatcher $matcher
    ) {
        $this->searchUrl = config('dhunzo.lyrics.providers.kugou.search_url', 'http://lyrics.kugou.com/search');
        $this->downloadUrl = config('dhunzo.lyrics.providers.kugou.download_url', 'http://lyrics.kugou.com/download');
        $this->timeoutMs = (int) config('dhunzo.lyrics.timeout_ms', 2500);
    }

    public function getName(): string
    {
        return 'kugou';
    }

    public function searchLyrics(string $title, ?string $artist = null, ?string $album = null, ?int $duration = null): ?LyricsDTO
    {
        try {
            $keyword = trim("{$title} {$artist}");
            $durationMs = $duration ? $duration * 1000 : 0;
            $timeoutSec = (int) ceil($this->timeoutMs / 1000.0);

            $response = Http::timeout($timeoutSec)
                ->get($this->searchUrl, [
                    'ver'      => '1',
                    'man'      => 'yes',
                    'client'   => 'pc',
                    'keyword'  => $keyword,
                    'duration' => $durationMs,
                    'hash'     => '',
                ]);

            if (!$response->successful()) {
                return null;
            }

            $candidates = $response->json()['candidates'] ?? [];
            if (empty($candidates) || !is_array($candidates)) {
                return null;
            }

            $bestCandidate = null;
            $bestScore = 0.0;

            foreach ($candidates as $cand) {
                $candDurationSec = isset($cand['duration']) ? (int) round($cand['duration'] / 1000) : null;
                $score = $this->matcher->score(
                    queryTitle: $title,
                    queryArtist: $artist,
                    queryAlbum: $album,
                    queryDuration: $duration,
                    candTitle: $cand['song'] ?? '',
                    candArtist: $cand['singer'] ?? null,
                    candAlbum: null,
                    candDuration: $candDurationSec
                );

                if ($this->matcher->isMatch($score) && $score > $bestScore) {
                    $bestCandidate = $cand;
                    $bestScore = $score;
                }
            }

            if (!$bestCandidate || empty($bestCandidate['id']) || empty($bestCandidate['accesskey'])) {
                return null;
            }

            // Download candidate lyrics
            $downResp = Http::timeout($timeoutSec)
                ->get($this->downloadUrl, [
                    'ver'       => '1',
                    'client'    => 'pc',
                    'id'        => $bestCandidate['id'],
                    'accesskey' => $bestCandidate['accesskey'],
                    'fmt'       => 'lrc',
                    'charset'   => 'utf8',
                ]);

            if (!$downResp->successful()) {
                return null;
            }

            $base64Content = $downResp->json()['content'] ?? '';
            if (empty($base64Content)) {
                return null;
            }

            $lrc = base64_decode($base64Content);
            if (empty($lrc)) {
                return null;
            }

            $normalized = $this->normalizer->normalize($lrc, 'line_synced');

            return new LyricsDTO(
                id: 'kugou:' . $bestCandidate['id'],
                provider: 'kugou',
                type: $normalized['type'],
                lines: $normalized['lines'],
                raw: $lrc,
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
}
