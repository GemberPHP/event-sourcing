<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Registry\Saga\Cached;

use Gember\EventSourcing\Registry\Saga\SagaRegistry;
use Gember\EventSourcing\Resolver\Saga\SagaDefinition;
use Override;
use Psr\SimpleCache\CacheInterface;

/**
 * @phpstan-import-type SagaDefinitionPayload from SagaDefinition
 */
final readonly class CachedSagaRegistryDecorator implements SagaRegistry
{
    private const string CACHE_KEY = 'gember.registry.saga.%s';

    public function __construct(
        private SagaRegistry $sagaRegistry,
        private CacheInterface $cache,
    ) {}

    #[Override]
    public function retrieve(string $sagaIdName): array
    {
        $cacheKey = $this->createCacheKey($sagaIdName);

        if (!$this->cache->has($cacheKey)) {
            $definitions = $this->sagaRegistry->retrieve($sagaIdName);

            $this->cache->set($cacheKey, json_encode(
                array_map(fn($definition) => $definition->toPayload(), $definitions),
                JSON_THROW_ON_ERROR,
            ));

            return $definitions;
        }

        /** @var list<SagaDefinitionPayload> $cachedDefinitions */
        $cachedDefinitions = json_decode($this->cache->get($cacheKey), true, flags: JSON_THROW_ON_ERROR);

        return array_map(fn($cachedDefinition) => SagaDefinition::fromPayload($cachedDefinition), $cachedDefinitions);
    }

    private function createCacheKey(string $sagaIdName): string
    {
        return sprintf(self::CACHE_KEY, $sagaIdName);
    }
}
