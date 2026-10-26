<?php

namespace App\Http\Controllers\Api\V1\Music;

use App\Http\Controllers\Controller;
use App\Services\Music\MusicService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        protected MusicService $musicService
    ) {}

    public function search(Request $request): JsonResponse
    {
        $rawQuery = $request->input('q') ?? $request->input('query') ?? $request->input('search');

        if (empty($rawQuery) || !is_string($rawQuery)) {
            return ApiResponse::error(
                code: 'INVALID_REQUEST',
                message: 'The query parameter (q or query) is required.',
                details: ['q' => ['The query parameter (q or query) is required.']],
                status: 422
            );
        }

        $query = trim($rawQuery);
        $provider = $request->input('provider');
        $page = max(1, (int) ($request->input('page') ?? 1));
        $limit = max(1, min(50, (int) ($request->input('limit') ?? 20)));

        $result = $this->musicService->search($query, $provider, $page, $limit);

        return ApiResponse::success(
            data: $result['songs'],
            meta: [
                'provider' => $result['provider'],
                'page'     => $result['page'],
                'limit'    => $result['limit'],
            ]
        );
    }
}
