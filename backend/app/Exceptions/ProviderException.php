<?php

namespace App\Exceptions;

use Exception;

class ProviderException extends Exception
{
    protected string $provider;
    protected mixed $details;

    public function __construct(string $message = 'Provider error occurred.', string $provider = 'unknown', mixed $details = null, int $code = 502, ?\Throwable $previous = null)
    {
        $this->provider = $provider;
        $this->details = $details;
        parent::__construct($message, $code, $previous);
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getDetails(): mixed
    {
        return $this->details;
    }
}
