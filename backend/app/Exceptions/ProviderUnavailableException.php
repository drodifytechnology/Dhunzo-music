<?php

namespace App\Exceptions;

class ProviderUnavailableException extends ProviderException
{
    public function __construct(string $message = 'Provider is currently unavailable.', string $provider = 'unknown', mixed $details = null, int $code = 503, ?\Throwable $previous = null)
    {
        parent::__construct($message, $provider, $details, $code, $previous);
    }
}
