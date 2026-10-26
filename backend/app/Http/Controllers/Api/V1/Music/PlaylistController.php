<?php

namespace App\Http\Controllers\Api\V1\Music;

use App\Http\Controllers\Controller;
use App\Services\Music\MusicService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaylistController extends Controller
{
    public function __construct(
        protected MusicService $musicService
    ) {}

    public function show(Request $request, string $id): JsonResponse
    {
        $provider = $request->query('provider');
        $playlist = $this->musicService->getPlaylist($id, $provider);

        if (!$playlist) {
            return ApiResponse::error(
                code: ApiResponse::ERR_NOT_FOUND,
                message: "Playlist with ID '{$id}' not found.",
                status: 404
            );
        }

        return ApiResponse::success(
            data: $playlist,
            meta: [
                'provider' => $playlist->provider,
            ]
        );
    }

    public function songs(Request $request, string $id): JsonResponse
    {
        $provider = $request->query('provider');
        $songs = $this->musicService->getPlaylistSongs($id, $provider);

        return ApiResponse::success(
            data: $songs,
            meta: [
                'limit' => count($songs),
            ]
        );
    }
}
