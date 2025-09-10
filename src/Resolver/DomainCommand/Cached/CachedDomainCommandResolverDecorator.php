<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainCommand\Cached;

use Gember\EventSourcing\Resolver\DomainCommand\DomainCommandDefinition;
use Gember\EventSourcing\Resolver\DomainCommand\DomainCommandResolver;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\FriendlyClassNamer;
use Psr\SimpleCache\CacheInterface;
use Override;
use JsonException;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @phpstan-import-type DomainCommandDefinitionPayload from DomainCommandDefinition
 */
final readonly class CachedDomainCommandResolverDecorator implements DomainCommandResolver
{
    private const string CACHE_KEY = 'gember.resolver.domain_command.%s';

    public function __construct(
        private DomainCommandResolver $domainCommandResolver,
        private CacheInterface $cache,
        private FriendlyClassNamer $friendlyClassNamer,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    #[Override]
    public function resolve(string $commandClassName): DomainCommandDefinition
    {
        $cacheKey = $this->createCacheKey($commandClassName);

        if (!$this->cache->has($cacheKey)) {
            $definition = $this->domainCommandResolver->resolve($commandClassName);

            $this->cache->set($cacheKey, json_encode($definition->toPayload(), JSON_THROW_ON_ERROR));

            return $definition;
        }

        /** @var string $cachedDefinition */
        $cachedDefinition = $this->cache->get($cacheKey);

        /** @var DomainCommandDefinitionPayload $payload */
        $payload = json_decode($cachedDefinition, true, flags: JSON_THROW_ON_ERROR);

        return DomainCommandDefinition::fromPayload($payload);
    }

    /**
     * @param class-string $eventClassName
     */
    private function createCacheKey(string $eventClassName): string
    {
        return sprintf(self::CACHE_KEY, $this->friendlyClassNamer->createFriendlyClassName($eventClassName));
    }
}
