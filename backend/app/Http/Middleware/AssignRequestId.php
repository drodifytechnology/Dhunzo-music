<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignRequestId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-ID');

        if (empty($requestId) || !Str::isUuid($requestId)) {
            $requestId = (string) Str::uuid();
        }

        // Set on request header and attributes for easy access
        $request->headers->set('X-Request-ID', $requestId);
        $request->attributes->set('request_id', $requestId);

        $response = $next($request);

        $response->headers->set('X-Request-ID', $requestId);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        \Illuminate\Support\Facades\Log::info($request->method() . ' ' . $request->fullUrl() . ' [' . $response->getStatusCode() . ']');

        return $response;
    }
}
