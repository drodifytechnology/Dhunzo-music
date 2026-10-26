<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Lyrics\LyricsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LyricsController extends Controller
{
    public function __construct(
        protected LyricsService $lyricsService
    ) {}

    /**
     * Search lyrics by title, artist, album, and duration.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'       => 'required|string|min:1|max:255',
            'artist'      => 'nullable|string|max:255',
            'album'       => 'nullable|string|max:255',
            'duration'    => 'nullable|integer|min:1',
            'duration_ms' => 'nullable|integer|min:1',
            'audio_url'   => 'nullable|url|max:1024',
        ]);

        $title = $validated['title'];
        $artist = $validated['artist'] ?? null;
        $album = $validated['album'] ?? null;
        $duration = isset($validated['duration'])
            ? (int) $validated['duration']
            : (isset($validated['duration_ms']) ? (int) round($validated['duration_ms'] / 1000) : null);
        $audioUrl = $validated['audio_url'] ?? null;

        $lyrics = $this->lyricsService->search($title, $artist, $album, $duration, $audioUrl);

        if (!$lyrics) {
            return ApiResponse::error(
                code: ApiResponse::ERR_NOT_FOUND,
                message: 'No matching lyrics found.',
                status: 404
            );
        }

        return ApiResponse::success(
            data: $lyrics,
            meta: [
                'provider' => $lyrics->provider,
                'type'     => $lyrics->type,
            ]
        );
    }

    /**
     * Get lyrics by song ID or provider lyrics ID.
     */
    public function show(Request $request, string $song_id): JsonResponse
    {
        // Check if ID looks like provider lyrics ID (e.g. 'lrclib:123', 'ai:hash') or song ID
        $lyrics = null;
        if (str_starts_with($song_id, 'lrclib:') || str_starts_with($song_id, 'betterlyrics:') || str_starts_with($song_id, 'kugou:') || str_starts_with($song_id, 'netease:') || str_starts_with($song_id, 'ai:')) {
            $lyrics = $this->lyricsService->getById($song_id);
        } else {
            $lyrics = $this->lyricsService->getBySongId($song_id);
        }

        if (!$lyrics) {
            return ApiResponse::error(
                code: ApiResponse::ERR_NOT_FOUND,
                message: "Lyrics for song '{$song_id}' could not be found.",
                status: 404
            );
        }

        return ApiResponse::success(
            data: $lyrics,
            meta: [
                'provider' => $lyrics->provider,
                'type'     => $lyrics->type,
            ]
        );
    }
}
