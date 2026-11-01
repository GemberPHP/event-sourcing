<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Saga\Cached;

use Gember\EventSourcing\Resolver\Saga\SagaDefinition;
use Gember\EventSourcing\Resolver\Saga\SagaResolver;
use Gember\EventSourcing\Util\Cache\CacheKey;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\FriendlyClassNamer;
use Override;
use Psr\SimpleCache\CacheInterface;
use JsonException;
use TypeError;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @phpstan-import-type SagaDefinitionPayload from SagaDefinition
 */
final readonly class CachedSagaResolverDecorator implements SagaResolver
{
    private const string CACHE_TYPE = 'resolver.saga';

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
        $cachedDefinition = $this->cache->get($cacheKey);

        if (is_string($cachedDefinition)) {
            try {
                /** @var SagaDefinitionPayload $payload */
                $payload = json_decode($cachedDefinition, true, flags: JSON_THROW_ON_ERROR);

                return SagaDefinition::fromPayload($payload);
            } catch (JsonException|TypeError) {
                // Unreadable entry: resolve it again and overwrite it
            }
        }

        $definition = $this->sagaResolver->resolve($sagaClassName);

        $this->cache->set($cacheKey, json_encode($definition->toPayload(), JSON_THROW_ON_ERROR));

        return $definition;
    }

    /**
     * @param class-string $className
     */
    private function createCacheKey(string $className): string
    {
        return CacheKey::create(self::CACHE_TYPE, $this->friendlyClassNamer->createFriendlyClassName($className));
    }
}
