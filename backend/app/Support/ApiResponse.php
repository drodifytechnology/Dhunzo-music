<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ApiResponse
{
    // Standard error codes defined in TASK-00
    public const ERR_INVALID_REQUEST      = 'INVALID_REQUEST';
    public const ERR_UNAUTHORIZED         = 'UNAUTHORIZED';
    public const ERR_NOT_FOUND            = 'NOT_FOUND';
    public const ERR_RATE_LIMITED         = 'RATE_LIMITED';
    public const ERR_PROVIDER_ERROR       = 'PROVIDER_ERROR';
    public const ERR_PROVIDER_UNAVAILABLE = 'PROVIDER_UNAVAILABLE';
    public const ERR_PROVIDER_TIMEOUT     = 'PROVIDER_TIMEOUT';
    public const ERR_INTERNAL_ERROR       = 'INTERNAL_ERROR';

    /**
     * Return a standardized success JSON response.
     *
     * @param mixed $data
     * @param array $meta
     * @param int $status
     * @return JsonResponse
     */
    public static function success(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        $requestId = self::resolveRequestId();

        $mergedMeta = array_merge([
            'provider'   => $meta['provider'] ?? 'system',
            'page'       => (int) ($meta['page'] ?? 1),
            'limit'      => (int) ($meta['limit'] ?? (is_countable($data) ? count($data) : 1)),
            'request_id' => $requestId,
        ], $meta);

        // Ensure request_id is always present in meta as well
        $mergedMeta['request_id'] = $requestId;

        return response()->json([
            'success'    => true,
            'data'       => $data,
            'meta'       => $mergedMeta,
            'error'      => null,
            'request_id' => $requestId,
        ], $status, [
            'X-Request-ID' => $requestId,
        ]);
    }

    /**
     * Return a standardized error JSON response.
     *
     * @param string $code
     * @param string $message
     * @param mixed $details
     * @param int $status
     * @param array $meta
     * @return JsonResponse
     */
    public static function error(
        string $code,
        string $message,
        mixed $details = null,
        int $status = 400,
        array $meta = []
    ): JsonResponse {
        $requestId = self::resolveRequestId();

        $mergedMeta = array_merge([
            'provider'   => $meta['provider'] ?? 'system',
            'page'       => (int) ($meta['page'] ?? 1),
            'limit'      => (int) ($meta['limit'] ?? 0),
            'request_id' => $requestId,
        ], $meta);

        $mergedMeta['request_id'] = $requestId;

        return response()->json([
            'success'    => false,
            'data'       => null,
            'meta'       => $mergedMeta,
            'error'      => [
                'code'    => $code,
                'message' => $message,
                'details' => $details,
            ],
            'request_id' => $requestId,
        ], $status, [
            'X-Request-ID' => $requestId,
        ]);
    }

    /**
     * Resolve the current request ID from request attributes, headers, or generate one.
     */
    public static function resolveRequestId(): string
    {
        if (app()->bound('request')) {
            $request = request();
            if ($request->attributes->has('request_id')) {
                return (string) $request->attributes->get('request_id');
            }
            if ($request->hasHeader('X-Request-ID')) {
                return (string) $request->header('X-Request-ID');
            }
        }

        return (string) Str::uuid();
    }
}
