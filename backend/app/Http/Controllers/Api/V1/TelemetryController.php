<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelemetryController extends Controller
{
    /**
     * Record a client fallback event when Primary Domain API fails.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recordFallbackEvent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason'           => 'required|string|max:100',
            'target_provider'  => 'required|string|max:50',
            'primary_endpoint' => 'nullable|string|max:255',
            'query'            => 'nullable|string|max:255',
            'latency_ms'       => 'nullable|numeric',
            'device_info'      => 'nullable|string|max:255',
        ]);

        Log::channel('single')->warning('[Fallback Telemetry] Client triggered failover to fallback provider', [
            'reason'           => $validated['reason'],
            'target_provider'  => $validated['target_provider'],
            'primary_endpoint' => $validated['primary_endpoint'] ?? null,
            'query'            => $validated['query'] ?? null,
            'latency_ms'       => $validated['latency_ms'] ?? null,
            'device_info'      => $validated['device_info'] ?? null,
            'client_ip'        => $request->ip(),
            'user_agent'       => $request->userAgent(),
            'timestamp'        => now()->toIso8601String(),
        ]);

        return ApiResponse::success([
            'recorded'    => true,
            'event'       => 'fallback_logged',
            'recorded_at' => now()->toIso8601String(),
        ], ['provider' => 'telemetry']);
    }
}
