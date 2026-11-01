<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Registry\Event\Cached;

use Gember\EventSourcing\Registry\Event\EventNotRegisteredException;
use Gember\EventSourcing\Registry\Event\EventRegistry;
use Gember\EventSourcing\Util\Cache\CacheKey;
use Override;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

final readonly class CachedEventRegistryDecorator implements EventRegistry
{
    private const string CACHE_TYPE = 'registry.event';

    public function __construct(
        private EventRegistry $eventRegistry,
        private CacheInterface $cache,
    ) {}

    /**
     * @throws EventNotRegisteredException
     * @throws InvalidArgumentException
     */
    #[Override]
    public function retrieve(string $eventName): string
    {
        $cacheKey = $this->createCacheKey($eventName);
        $cachedEventClassName = $this->cache->get($cacheKey);

        // A class that no longer exists (e.g. renamed since it was cached) is retrieved again
        if (is_string($cachedEventClassName) && class_exists($cachedEventClassName)) {
            return $cachedEventClassName;
        }

        $eventClassName = $this->eventRegistry->retrieve($eventName);

        $this->cache->set($cacheKey, $eventClassName);

        return $eventClassName;
    }

    private function createCacheKey(string $eventName): string
    {
        return CacheKey::create(self::CACHE_TYPE, $eventName);
    }
}
