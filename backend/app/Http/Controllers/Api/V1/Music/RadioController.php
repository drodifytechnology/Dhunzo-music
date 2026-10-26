<?php

namespace App\Http\Controllers\Api\V1\Music;

use App\Http\Controllers\Controller;
use App\Services\Music\MusicService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RadioController extends Controller
{
    public function __construct(
        protected MusicService $musicService
    ) {}

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string',
            'type'     => 'nullable|string|in:featured,artist',
            'provider' => 'nullable|string|in:saavn,youtube',
        ]);

        $name = $validated['name'];
        $type = $validated['type'] ?? 'featured';
        $provider = $validated['provider'] ?? null;

        $stationId = $this->musicService->createRadio($name, $type, $provider);

        if (!$stationId) {
            return ApiResponse::error(
                code: ApiResponse::ERR_PROVIDER_ERROR,
                message: 'Failed to create radio station.',
                status: 502
            );
        }

        return ApiResponse::success(
            data: [
                'station_id' => $stationId,
                'name'       => $name,
                'type'       => $type,
            ],
            meta: [
                'provider' => $provider ?? 'saavn',
            ],
            status: 201
        );
    }

    public function song(Request $request, string $stationId): JsonResponse
    {
        $provider = $request->query('provider');
        $song = $this->musicService->getRadioSong($stationId, $provider);

        if (!$song) {
            return ApiResponse::error(
                code: ApiResponse::ERR_NOT_FOUND,
                message: "No song available for radio station '{$stationId}'.",
                status: 404
            );
        }

        return ApiResponse::success(
            data: $song,
            meta: [
                'provider'   => $song->provider,
                'station_id' => $stationId,
            ]
        );
    }
}
