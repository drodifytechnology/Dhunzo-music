<?php

namespace App\Http\Controllers\Api\V1\Music;

use App\Http\Controllers\Controller;
use App\Services\Music\MusicService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SongController extends Controller
{
    public function __construct(
        protected MusicService $musicService
    ) {}

    public function show(Request $request, string $id): JsonResponse
    {
        $provider = $request->query('provider');
        $song = $this->musicService->getSong($id, $provider);

        if (!$song) {
            return ApiResponse::error(
                code: ApiResponse::ERR_NOT_FOUND,
                message: "Song with ID '{$id}' not found.",
                status: 404
            );
        }

        return ApiResponse::success(
            data: $song,
            meta: [
                'provider' => $song->provider,
            ]
        );
    }

    public function recommendations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'song_id'  => 'required|string',
            'provider' => 'nullable|string|in:saavn,youtube',
            'limit'    => 'nullable|integer|min:1|max:50',
        ]);

        $songId = $validated['song_id'];
        $provider = $validated['provider'] ?? null;
        $limit = (int) ($validated['limit'] ?? 10);

        $songs = $this->musicService->getRecommendations($songId, $provider, $limit);

        return ApiResponse::success(
            data: $songs,
            meta: [
                'provider' => $provider ?? 'auto',
                'limit'    => $limit,
            ]
        );
    }

    public function trending(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'nullable|string|in:saavn,youtube',
            'limit'    => 'nullable|integer|min:1|max:50',
        ]);

        $provider = $validated['provider'] ?? null;
        $limit = (int) ($validated['limit'] ?? 20);

        $songs = $this->musicService->getTrending($provider, $limit);

        return ApiResponse::success(
            data: $songs,
            meta: [
                'provider' => $provider ?? config('dhunzo.music.default_provider', 'saavn'),
                'limit'    => $limit,
            ]
        );
    }
}
