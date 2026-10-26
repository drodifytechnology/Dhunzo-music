<?php

namespace App\Exceptions;

class ProviderTimeoutException extends ProviderException
{
    public function __construct(string $message = 'Provider request timed out.', string $provider = 'unknown', mixed $details = null, int $code = 504, ?\Throwable $previous = null)
    {
        parent::__construct($message, $provider, $details, $code, $previous);
    }
}
