<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainCommand\Cached;

use Gember\EventSourcing\Resolver\DomainCommand\DomainCommandDefinition;
use Gember\EventSourcing\Resolver\DomainCommand\DomainCommandResolver;
use Gember\EventSourcing\Util\Cache\CacheKey;
use Psr\SimpleCache\CacheInterface;
use Override;
use JsonException;
use TypeError;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @phpstan-import-type DomainCommandDefinitionPayload from DomainCommandDefinition
 */
final readonly class CachedDomainCommandResolverDecorator implements DomainCommandResolver
{
    private const string CACHE_TYPE = 'resolver.domain_command';

    public function __construct(
        private DomainCommandResolver $domainCommandResolver,
        private CacheInterface $cache,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    #[Override]
    public function resolve(string $commandClassName): DomainCommandDefinition
    {
        $cacheKey = $this->createCacheKey($commandClassName);
        $cachedDefinition = $this->cache->get($cacheKey);

        if (is_string($cachedDefinition)) {
            try {
                /** @var DomainCommandDefinitionPayload $payload */
                $payload = json_decode($cachedDefinition, true, flags: JSON_THROW_ON_ERROR);

                return DomainCommandDefinition::fromPayload($payload);
            } catch (JsonException|TypeError) {
                // Unreadable entry: resolve it again and overwrite it
            }
        }

        $definition = $this->domainCommandResolver->resolve($commandClassName);

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
