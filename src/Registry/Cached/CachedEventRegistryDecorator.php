<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Registry\Cached;

use Gember\EventSourcing\Registry\EventNotRegisteredException;
use Gember\EventSourcing\Registry\EventRegistry;
use Gember\EventSourcing\Util\Cache\Cache;
use Gember\EventSourcing\Util\Cache\CacheException;
use Override;

final readonly class CachedEventRegistryDecorator implements EventRegistry
{
    private const string CACHE_KEY = 'gember.event-registry.%s';

    /**
     * @param Cache<class-string> $cache
     */
    public function __construct(
        private EventRegistry $eventRegistry,
        private Cache $cache,
    ) {}

    /**
     * @throws EventNotRegisteredException
     * @throws CacheException
     */
    #[Override]
    public function retrieve(string $eventName): string
    {
        $cacheKey = $this->createCacheKey($eventName);

        if (!$this->cache->has($cacheKey)) {
            $eventClassName = $this->eventRegistry->retrieve($eventName);

            $this->cache->set($cacheKey, $eventClassName);

            return $eventClassName;
        }

        return $this->cache->get($cacheKey);
    }

    private function createCacheKey(string $eventName): string
    {
        return sprintf(self::CACHE_KEY, $eventName);
    }
}
