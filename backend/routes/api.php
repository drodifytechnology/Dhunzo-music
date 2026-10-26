<?php

use App\Http\Controllers\Api\V1\LyricsController;
use App\Http\Controllers\Api\V1\Music\AlbumController;
use App\Http\Controllers\Api\V1\Music\ArtistController;
use App\Http\Controllers\Api\V1\Music\PlaylistController;
use App\Http\Controllers\Api\V1\Music\RadioController;
use App\Http\Controllers\Api\V1\Music\SearchController;
use App\Http\Controllers\Api\V1\Music\SongController;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Production Health Check
Route::get('/health', function () {
    $status = 'healthy';
    $checks = [];

    // Check Database
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $checks['database'] = 'ok';
    } catch (\Throwable $e) {
        $status = 'degraded';
        $checks['database'] = 'error';
    }

    // Check Cache
    try {
        \Illuminate\Support\Facades\Cache::put('health_check_ping', 'pong', 5);
        $checks['cache'] = \Illuminate\Support\Facades\Cache::get('health_check_ping') === 'pong' ? 'ok' : 'failed';
    } catch (\Throwable $e) {
        $status = 'degraded';
        $checks['cache'] = 'error';
    }

    // Check Storage
    $checks['storage'] = is_writable(storage_path('framework/cache')) ? 'ok' : 'read_only';

    return ApiResponse::success([
        'status'    => $status,
        'service'   => config('dhunzo.app_name', 'BlackHole Lyrics & Music API'),
        'checks'    => $checks,
        'timestamp' => now()->toIso8601String(),
    ]);
});

// V1 Search Aliases
Route::get('/v1/search', [SearchController::class, 'search']);
Route::get('/v1/songs/search', [SearchController::class, 'search']);

// V1 Telemetry Fallback Logging
Route::post('/v1/telemetry/fallback-event', [\App\Http\Controllers\Api\V1\TelemetryController::class, 'recordFallbackEvent']);

// V1 Music Domain Routes
Route::prefix('v1/music')->group(function () {
    Route::get('/search', [SearchController::class, 'search']);

    Route::get('/trending', [SongController::class, 'trending']);
    Route::get('/recommendations', [SongController::class, 'recommendations']);

    Route::get('/songs/{id}', [SongController::class, 'show']);

    Route::get('/albums/{id}', [AlbumController::class, 'show']);
    Route::get('/albums/{id}/songs', [AlbumController::class, 'songs']);

    Route::get('/artists/{id}', [ArtistController::class, 'show']);
    Route::get('/artists/{id}/songs', [ArtistController::class, 'songs']);

    Route::get('/playlists/{id}', [PlaylistController::class, 'show']);
    Route::get('/playlists/{id}/songs', [PlaylistController::class, 'songs']);

    Route::post('/radio', [RadioController::class, 'create']);
    Route::get('/radio/{stationId}/songs', [RadioController::class, 'song']);
});

// V1 Lyrics Domain Routes with Rate Limiting (throttle:60,1)
Route::prefix('v1/lyrics')->middleware('throttle:60,1')->group(function () {
    Route::get('/search', [LyricsController::class, 'search']);
    Route::get('/{song_id}', [LyricsController::class, 'show']);
});

// Legacy BlackHole Compatibility Proxy
Route::any('/api.php', [\App\Http\Controllers\Api\LegacySaavnProxyController::class, 'handle']);
