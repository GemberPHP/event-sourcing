<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Registry\Event\Cached;

use Gember\EventSourcing\Registry\Event\EventNotRegisteredException;
use Gember\EventSourcing\Registry\Event\EventRegistry;
use Override;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

final readonly class CachedEventRegistryDecorator implements EventRegistry
{
    private const string CACHE_KEY = 'gember.registry.event.%s';

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

        if (!$this->cache->has($cacheKey)) {
            $eventClassName = $this->eventRegistry->retrieve($eventName);

            $this->cache->set($cacheKey, $eventClassName);

            return $eventClassName;
        }

        /** @var class-string */
        return $this->cache->get($cacheKey);
    }

    private function createCacheKey(string $eventName): string
    {
        return sprintf(self::CACHE_KEY, $eventName);
    }
}
