<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Convert an exception into a standardized API response envelope.
     */
    protected function handleApiException($request, Throwable $e)
    {
        // 1. Validation error
        if ($e instanceof ValidationException) {
            return ApiResponse::error(
                ApiResponse::ERR_INVALID_REQUEST,
                'The given data was invalid.',
                $e->errors(),
                422
            );
        }

        // 2. Authentication error
        if ($e instanceof AuthenticationException || $e instanceof UnauthorizedHttpException) {
            return ApiResponse::error(
                ApiResponse::ERR_UNAUTHORIZED,
                $e->getMessage() ?: 'Unauthenticated.',
                null,
                401
            );
        }

        // 3. Not Found error
        if ($e instanceof NotFoundHttpException || $e instanceof ModelNotFoundException) {
            return ApiResponse::error(
                ApiResponse::ERR_NOT_FOUND,
                'The requested resource could not be found.',
                null,
                404
            );
        }

        // 4. Rate Limited error
        if ($e instanceof ThrottleRequestsException || $e instanceof TooManyRequestsHttpException) {
            return ApiResponse::error(
                ApiResponse::ERR_RATE_LIMITED,
                'Too many requests. Please slow down.',
                null,
                429
            );
        }

        // 5. Custom Provider Exceptions
        if ($e instanceof ProviderTimeoutException) {
            return ApiResponse::error(
                ApiResponse::ERR_PROVIDER_TIMEOUT,
                $e->getMessage(),
                $e->getDetails(),
                504,
                ['provider' => $e->getProvider()]
            );
        }

        if ($e instanceof ProviderUnavailableException) {
            return ApiResponse::error(
                ApiResponse::ERR_PROVIDER_UNAVAILABLE,
                $e->getMessage(),
                $e->getDetails(),
                503,
                ['provider' => $e->getProvider()]
            );
        }

        if ($e instanceof ProviderException) {
            return ApiResponse::error(
                ApiResponse::ERR_PROVIDER_ERROR,
                $e->getMessage(),
                $e->getDetails(),
                502,
                ['provider' => $e->getProvider()]
            );
        }

        // 6. Generic HTTP Exceptions
        if ($e instanceof HttpExceptionInterface) {
            $statusCode = $e->getStatusCode();
            $code = match ($statusCode) {
                400 => ApiResponse::ERR_INVALID_REQUEST,
                401 => ApiResponse::ERR_UNAUTHORIZED,
                404 => ApiResponse::ERR_NOT_FOUND,
                429 => ApiResponse::ERR_RATE_LIMITED,
                502 => ApiResponse::ERR_PROVIDER_ERROR,
                503 => ApiResponse::ERR_PROVIDER_UNAVAILABLE,
                504 => ApiResponse::ERR_PROVIDER_TIMEOUT,
                default => ApiResponse::ERR_INTERNAL_ERROR,
            };

            return ApiResponse::error(
                $code,
                $e->getMessage() ?: 'An HTTP error occurred.',
                null,
                $statusCode
            );
        }

        // 7. Internal / Unhandled Server Error (never expose raw traces to clients)
        return ApiResponse::error(
            ApiResponse::ERR_INTERNAL_ERROR,
            'An unexpected internal server error occurred.',
            config('app.debug') ? [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
            ] : null,
            500
        );
    }
}
