<?php

namespace Tests\Unit\Lyrics;

use App\Services\Lyrics\CircuitBreaker;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CircuitBreakerTest extends TestCase
{
    protected CircuitBreaker $circuitBreaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->circuitBreaker = new CircuitBreaker();
    }

    public function test_circuit_breaker_trips_after_five_failures(): void
    {
        $provider = 'test_lyrics_provider';
        $this->circuitBreaker->reset($provider);

        $this->assertTrue($this->circuitBreaker->isAvailable($provider));

        // 4 failures - still closed
        for ($i = 0; $i < 4; $i++) {
            $this->circuitBreaker->recordFailure($provider);
            $this->assertTrue($this->circuitBreaker->isAvailable($provider));
        }

        // 5th failure - trips open
        $this->circuitBreaker->recordFailure($provider);
        $this->assertFalse($this->circuitBreaker->isAvailable($provider));
    }

    public function test_circuit_breaker_resets_on_success(): void
    {
        $provider = 'another_provider';
        $this->circuitBreaker->reset($provider);

        $this->circuitBreaker->recordFailure($provider);
        $this->circuitBreaker->recordFailure($provider);

        $this->circuitBreaker->recordSuccess($provider);

        $this->assertTrue($this->circuitBreaker->isAvailable($provider));
    }
}
