<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\Cached;

use Gember\EventSourcing\Resolver\DomainEvent\DomainEventDefinition;
use Gember\EventSourcing\Resolver\DomainEvent\DomainEventResolver;
use Gember\EventSourcing\Util\Cache\CacheKey;
use Psr\SimpleCache\CacheInterface;
use Override;
use JsonException;
use TypeError;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @phpstan-import-type DomainEventDefinitionPayload from DomainEventDefinition
 */
final readonly class CachedDomainEventResolverDecorator implements DomainEventResolver
{
    private const string CACHE_TYPE = 'resolver.domain_event';

    public function __construct(
        private DomainEventResolver $domainEventResolver,
        private CacheInterface $cache,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    #[Override]
    public function resolve(string $eventClassName): DomainEventDefinition
    {
        $cacheKey = $this->createCacheKey($eventClassName);
        $cachedDefinition = $this->cache->get($cacheKey);

        if (is_string($cachedDefinition)) {
            try {
                /** @var DomainEventDefinitionPayload $payload */
                $payload = json_decode($cachedDefinition, true, flags: JSON_THROW_ON_ERROR);

                return DomainEventDefinition::fromPayload($payload);
            } catch (JsonException|TypeError) {
                // Unreadable entry: resolve it again and overwrite it
            }
        }

        $definition = $this->domainEventResolver->resolve($eventClassName);

        $this->cache->set($cacheKey, json_encode($definition->toPayload(), JSON_THROW_ON_ERROR));

        return $definition;
    }

    /**
     * @param class-string $className
     */
    private function createCacheKey(string $className): string
    {
        return CacheKey::create(self::CACHE_TYPE, $className);
    }
}
