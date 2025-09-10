<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\Cached;

use Gember\EventSourcing\Resolver\DomainEvent\DomainEventDefinition;
use Gember\EventSourcing\Resolver\DomainEvent\DomainEventResolver;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\FriendlyClassNamer;
use Psr\SimpleCache\CacheInterface;
use Override;
use JsonException;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @phpstan-import-type DomainEventDefinitionPayload from DomainEventDefinition
 */
final readonly class CachedDomainEventResolverDecorator implements DomainEventResolver
{
    private const string CACHE_KEY = 'gember.resolver.domain_event.%s';

    public function __construct(
        private DomainEventResolver $domainEventResolver,
        private CacheInterface $cache,
        private FriendlyClassNamer $friendlyClassNamer,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    #[Override]
    public function resolve(string $eventClassName): DomainEventDefinition
    {
        $cacheKey = $this->createCacheKey($eventClassName);

        if (!$this->cache->has($cacheKey)) {
            $definition = $this->domainEventResolver->resolve($eventClassName);

            $this->cache->set($cacheKey, json_encode($definition->toPayload(), JSON_THROW_ON_ERROR));

            return $definition;
        }

        /** @var string $cachedDefinition */
        $cachedDefinition = $this->cache->get($cacheKey);

        /** @var DomainEventDefinitionPayload $payload */
        $payload = json_decode($cachedDefinition, true, flags: JSON_THROW_ON_ERROR);

        return DomainEventDefinition::fromPayload($payload);
    }

    /**
     * @param class-string $eventClassName
     */
    private function createCacheKey(string $eventClassName): string
    {
        return sprintf(self::CACHE_KEY, $this->friendlyClassNamer->createFriendlyClassName($eventClassName));
    }
}
