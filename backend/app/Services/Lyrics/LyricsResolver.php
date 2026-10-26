<?php

namespace App\Services\Lyrics;

use App\DTOs\Lyrics\LyricsDTO;
use App\Services\Lyrics\Contracts\LyricsProviderInterface;
use App\Services\Lyrics\Providers\BetterLyricsProvider;
use App\Services\Lyrics\Providers\GeniusProvider;
use App\Services\Lyrics\Providers\KuGouProvider;
use App\Services\Lyrics\Providers\LrcLibProvider;
use App\Services\Lyrics\Providers\MusixmatchProvider;
use App\Services\Lyrics\Providers\NetEaseProvider;
use App\Services\Lyrics\Providers\YouTubeCaptionProvider;
use Throwable;

class LyricsResolver
{
    /**
     * @var array<int, LyricsProviderInterface>
     */
    protected array $providers = [];

    public function __construct(
        protected CircuitBreaker $circuitBreaker,
        LrcLibProvider $lrcLibProvider,
        BetterLyricsProvider $betterLyricsProvider,
        KuGouProvider $kuGouProvider,
        NetEaseProvider $netEaseProvider,
        MusixmatchProvider $musixmatchProvider,
        GeniusProvider $geniusProvider,
        YouTubeCaptionProvider $youTubeCaptionProvider
    ) {
        $this->providers = [
            $lrcLibProvider,
            $betterLyricsProvider,
            $kuGouProvider,
            $netEaseProvider,
            $musixmatchProvider,
            $geniusProvider,
            $youTubeCaptionProvider,
        ];
    }

    /**
     * Resolve best lyrics from providers pool.
     */
    public function resolve(string $title, ?string $artist = null, ?string $album = null, ?int $duration = null): ?LyricsDTO
    {
        $plainCandidate = null;

        foreach ($this->providers as $provider) {
            $name = $provider->getName();

            // Circuit Breaker check
            if (!$this->circuitBreaker->isAvailable($name)) {
                continue;
            }

            try {
                $lyrics = $provider->searchLyrics($title, $artist, $album, $duration);

                if ($lyrics !== null) {
                    $this->circuitBreaker->recordSuccess($name);

                    // If synced lyrics found, immediately return it
                    if (in_array($lyrics->type, ['syllable_synced', 'word_synced', 'line_synced'])) {
                        return $lyrics;
                    }

                    // Otherwise store as plain text fallback candidate if better than previous
                    if ($plainCandidate === null || ($lyrics->score ?? 0) > ($plainCandidate->score ?? 0)) {
                        $plainCandidate = $lyrics;
                    }
                }
            } catch (Throwable) {
                $this->circuitBreaker->recordFailure($name);
            }
        }

        return $plainCandidate;
    }

    /**
     * Resolve by provider-specific lyrics ID (e.g. 'lrclib:12345').
     */
    public function resolveById(string $id): ?LyricsDTO
    {
        $providerName = str_contains($id, ':') ? explode(':', $id, 2)[0] : 'lrclib';

        foreach ($this->providers as $provider) {
            if ($provider->getName() === $providerName) {
                if (!$this->circuitBreaker->isAvailable($providerName)) {
                    return null;
                }

                try {
                    $lyrics = $provider->getLyricsById($id);
                    if ($lyrics) {
                        $this->circuitBreaker->recordSuccess($providerName);
                        return $lyrics;
                    }
                } catch (Throwable) {
                    $this->circuitBreaker->recordFailure($providerName);
                }
            }
        }

        return null;
    }
}
