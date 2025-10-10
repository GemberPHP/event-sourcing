<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Saga\Cached;

use Gember\EventSourcing\Resolver\Saga\SagaDefinition;
use Gember\EventSourcing\Resolver\Saga\SagaResolver;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\FriendlyClassNamer;
use Override;
use Psr\SimpleCache\CacheInterface;
use JsonException;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @phpstan-import-type SagaDefinitionPayload from SagaDefinition
 */
final readonly class CachedSagaResolverDecorator implements SagaResolver
{
    private const string CACHE_KEY = 'gember.resolver.saga.%s';

    public function __construct(
        private SagaResolver $sagaResolver,
        private CacheInterface $cache,
        private FriendlyClassNamer $friendlyClassNamer,
    ) {}

    /**
     * @throws JsonException
     * @throws InvalidArgumentException
     */
    #[Override]
    public function resolve(string $sagaClassName): SagaDefinition
    {
        $cacheKey = $this->createCacheKey($sagaClassName);

        if (!$this->cache->has($cacheKey)) {
            $definition = $this->sagaResolver->resolve($sagaClassName);

            $this->cache->set($cacheKey, json_encode($definition->toPayload(), JSON_THROW_ON_ERROR));

            return $definition;
        }

        /** @var string $cachedDefinition */
        $cachedDefinition = $this->cache->get($cacheKey);

        /** @var SagaDefinitionPayload $payload */
        $payload = json_decode($cachedDefinition, true, flags: JSON_THROW_ON_ERROR);

        return SagaDefinition::fromPayload($payload);
    }

    /**
     * @param class-string $eventClassName
     */
    private function createCacheKey(string $eventClassName): string
    {
        return sprintf(self::CACHE_KEY, $this->friendlyClassNamer->createFriendlyClassName($eventClassName));
    }
}
