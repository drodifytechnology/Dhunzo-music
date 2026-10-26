<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Lyrics\LyricsService;
use App\Services\Music\Providers\SaavnProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LegacySaavnProxyController extends Controller
{
    public function __construct(
        protected SaavnProvider $saavnProvider,
        protected LyricsService $lyricsService
    ) {}

    /**
     * Handle legacy BlackHole Saavn API calls.
     */
    public function handle(Request $request): JsonResponse
    {
        $call = $request->query('__call') ?? $request->input('__call');

        // Handle lyrics call
        if ($call === 'lyrics.getLyrics') {
            return $this->handleLyrics($request);
        }

        // Delegate to SaavnProvider
        $params = $request->all();
        $response = $this->saavnProvider->request($params);

        // Enhance payload with 500x500 artwork & direct 320kbps media urls
        $enhanced = $this->enhancePayload($response);

        return response()->json($enhanced);
    }

    /**
     * Handle legacy lyrics retrieval via Dhunzo intelligent lyrics resolver.
     */
    protected function handleLyrics(Request $request): JsonResponse
    {
        $lyricsId = $request->query('lyrics_id') ?? $request->input('lyrics_id');
        $title = $request->query('title') ?? $request->input('title') ?? '';
        $artist = $request->query('artist') ?? $request->input('artist') ?? '';

        $lyrics = null;
        if (!empty($title)) {
            $lyrics = $this->lyricsService->search($title, $artist);
        } elseif (!empty($lyricsId)) {
            $lyrics = $this->lyricsService->getBySongId('saavn:' . $lyricsId);
        }

        if ($lyrics) {
            return response()->json([
                'status'   => 'SUCCESS',
                'lyrics'   => $lyrics->raw ?? implode("\n", array_column($lyrics->lines, 'text')),
                'type'     => $lyrics->type === 'line_synced' ? 'lrc' : 'text',
                'provider' => $lyrics->provider,
                'source'   => 'Dhunzo:' . $lyrics->provider,
            ]);
        }

        return response()->json([
            'status' => 'FAILURE',
            'error'  => 'Lyrics not found.',
        ], 404);
    }

    /**
     * Recursively enhance Saavn payloads with 500x500 images and 320kbps stream links.
     */
    protected function enhancePayload(mixed $data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->enhancePayload($value);
            } elseif (is_string($value)) {
                // Upgrade image resolution to 500x500
                if (in_array($key, ['image', 'image_url']) && str_contains($value, 'saavncdn.com')) {
                    $data[$key] = preg_replace('/(50x50|150x150)/', '500x500', $value);
                }

                // Upgrade preview url to direct 320kbps
                if ($key === 'media_preview_url' && str_contains($value, 'preview.saavncdn.com')) {
                    $data['media_url'] = str_replace(
                        ['preview.saavncdn.com', '_96_p.mp4'],
                        ['aac.saavncdn.com', '_320.mp4'],
                        $value
                    );
                }
            }
        }

        return $data;
    }
}
