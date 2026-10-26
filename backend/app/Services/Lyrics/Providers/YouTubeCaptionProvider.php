<?php

namespace App\Services\Lyrics\Providers;

use App\DTOs\Lyrics\LyricsDTO;
use App\Services\Lyrics\Contracts\LyricsProviderInterface;
use App\Services\Lyrics\LyricsMatcher;
use App\Services\Lyrics\LyricsNormalizer;
use Illuminate\Support\Facades\Http;
use Throwable;

class YouTubeCaptionProvider implements LyricsProviderInterface
{
    protected int $timeoutMs;

    public function __construct(
        protected LyricsNormalizer $normalizer,
        protected LyricsMatcher $matcher
    ) {
        $this->timeoutMs = (int) config('dhunzo.lyrics.timeout_ms', 2500);
    }

    public function getName(): string
    {
        return 'youtube_caption';
    }

    public function searchLyrics(string $title, ?string $artist = null, ?string $album = null, ?int $duration = null): ?LyricsDTO
    {
        // Caption extraction directly from timedtext if video ID provided
        if (str_starts_with($title, 'youtube:')) {
            $videoId = substr($title, 8);
            return $this->getLyricsById($videoId);
        }

        return null;
    }

    public function getLyricsById(string $id): ?LyricsDTO
    {
        $videoId = str_starts_with($id, 'youtube:') ? substr($id, 8) : $id;

        try {
            $timeoutSec = (int) ceil($this->timeoutMs / 1000.0);

            // Fetch video watch page to find captionTracks
            $response = Http::timeout($timeoutSec)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get("https://www.youtube.com/watch?v={$videoId}");

            if (!$response->successful()) {
                return null;
            }

            $body = $response->body();
            if (!preg_match('/"captionTracks":\s*(\[.*?\])/', $body, $matches)) {
                return null;
            }

            $tracks = json_decode($matches[1], true);
            if (empty($tracks) || !is_array($tracks)) {
                return null;
            }

            // Pick english or first track
            $trackUrl = $tracks[0]['baseUrl'] ?? null;
            foreach ($tracks as $t) {
                if (isset($t['languageCode']) && str_starts_with($t['languageCode'], 'en')) {
                    $trackUrl = $t['baseUrl'];
                    break;
                }
            }

            if (!$trackUrl) {
                return null;
            }

            $xmlResp = Http::timeout($timeoutSec)->get($trackUrl);
            if (!$xmlResp->successful()) {
                return null;
            }

            $xml = simplexml_load_string($xmlResp->body());
            if (!$xml) {
                return null;
            }

            $lines = [];
            foreach ($xml->text as $node) {
                $startSec = (float) $node['start'];
                $durSec = (float) $node['dur'];
                $text = trim(html_entity_decode((string) $node, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                if (!empty($text)) {
                    $startMs = (int) round($startSec * 1000);
                    $endMs = (int) round(($startSec + $durSec) * 1000);
                    $lines[] = [
                        'start_ms' => $startMs,
                        'end_ms'   => $endMs,
                        'text'     => $text,
                    ];
                }
            }

            if (empty($lines)) {
                return null;
            }

            return new LyricsDTO(
                id: 'youtube_caption:' . $videoId,
                provider: 'youtube_caption',
                type: 'line_synced',
                lines: $lines,
                score: 0.90
            );
        } catch (Throwable) {
            return null;
        }
    }
}
