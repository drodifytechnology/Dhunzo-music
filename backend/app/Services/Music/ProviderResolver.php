<?php

namespace App\Services\Music;

use App\Exceptions\ProviderException;
use App\Services\Music\Contracts\MusicProviderInterface;
use App\Services\Music\Providers\SaavnProvider;
use App\Services\Music\Providers\YouTubeProvider;
use InvalidArgumentException;

class ProviderResolver
{
    /**
     * @var array<string, MusicProviderInterface>
     */
    protected array $providers = [];

    public function __construct(
        SaavnProvider $saavnProvider,
        YouTubeProvider $youtubeProvider
    ) {
        $this->register($saavnProvider);
        $this->register($youtubeProvider);
    }

    public function register(MusicProviderInterface $provider): void
    {
        $this->providers[strtolower($provider->getName())] = $provider;
    }

    /**
     * Resolve provider by explicit name or default provider.
     */
    public function resolve(?string $name = null): MusicProviderInterface
    {
        $providerName = strtolower($name ?: config('dhunzo.music.default_provider', 'saavn'));

        if (!isset($this->providers[$providerName])) {
            throw new ProviderException("Music provider '{$providerName}' is not supported.", 'system', null, 400);
        }

        return $this->providers[$providerName];
    }

    /**
     * Resolve provider based on ID prefix (e.g. 'saavn:123', 'youtube:abc') or fallback.
     */
    public function resolveFromId(string $id, ?string $fallback = null): MusicProviderInterface
    {
        if (str_contains($id, ':')) {
            $prefix = strtolower(explode(':', $id, 2)[0]);
            if (isset($this->providers[$prefix])) {
                return $this->providers[$prefix];
            }
        }

        return $this->resolve($fallback);
    }

    /**
     * Check if a provider exists.
     */
    public function has(string $name): bool
    {
        return isset($this->providers[strtolower($name)]);
    }
}
