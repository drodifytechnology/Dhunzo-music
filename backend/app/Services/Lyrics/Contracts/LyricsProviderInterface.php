<?php

namespace App\Services\Lyrics\Contracts;

use App\DTOs\Lyrics\LyricsDTO;

interface LyricsProviderInterface
{
    /**
     * Unique identifier of provider (e.g. 'lrclib', 'kugou', 'musixmatch', 'genius').
     */
    public function getName(): string;

    /**
     * Search and retrieve matching lyrics for a song.
     */
    public function searchLyrics(string $title, ?string $artist = null, ?string $album = null, ?int $duration = null): ?LyricsDTO;

    /**
     * Retrieve lyrics by provider-specific lyrics ID.
     */
    public function getLyricsById(string $id): ?LyricsDTO;
}
