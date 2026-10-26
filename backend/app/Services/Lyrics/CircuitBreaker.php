<?php

namespace App\Services\Lyrics;

use Illuminate\Support\Facades\Cache;

class CircuitBreaker
{
    protected int $failureThreshold;
    protected int $openDurationSeconds;

    public function __construct()
    {
        $this->failureThreshold = (int) config('dhunzo.lyrics.circuit_breaker.failure_threshold', 5);
        $this->openDurationSeconds = (int) config('dhunzo.lyrics.circuit_breaker.open_duration_seconds', 300);
    }

    /**
     * Check if a provider's circuit is currently closed/available.
     */
    public function isAvailable(string $provider): bool
    {
        $openKey = $this->getCircuitOpenKey($provider);
        return !Cache::has($openKey);
    }

    /**
     * Record a successful request, resetting failure counter.
     */
    public function recordSuccess(string $provider): void
    {
        Cache::forget($this->getFailureCountKey($provider));
        Cache::forget($this->getCircuitOpenKey($provider));
    }

    /**
     * Record a failure; if failures reach threshold, trip the circuit open.
     */
    public function recordFailure(string $provider): void
    {
        $countKey = $this->getFailureCountKey($provider);
        $count = (int) Cache::get($countKey, 0) + 1;

        if ($count >= $this->failureThreshold) {
            $this->trip($provider);
        } else {
            // Keep failure count alive for open duration
            Cache::put($countKey, $count, $this->openDurationSeconds);
        }
    }

    /**
     * Explicitly trip the circuit to open state (e.g. on 429 or 5xx).
     */
    public function trip(string $provider, ?int $duration = null): void
    {
        $duration = $duration ?? $this->openDurationSeconds;
        $openKey = $this->getCircuitOpenKey($provider);

        Cache::put($openKey, time() + $duration, $duration);
        Cache::forget($this->getFailureCountKey($provider));
    }

    /**
     * Manually reset the circuit for testing or recovery.
     */
    public function reset(string $provider): void
    {
        Cache::forget($this->getCircuitOpenKey($provider));
        Cache::forget($this->getFailureCountKey($provider));
    }

    protected function getCircuitOpenKey(string $provider): string
    {
        return "dhunzo:circuit_breaker:{$provider}:open";
    }

    protected function getFailureCountKey(string $provider): string
    {
        return "dhunzo:circuit_breaker:{$provider}:failures";
    }
}
