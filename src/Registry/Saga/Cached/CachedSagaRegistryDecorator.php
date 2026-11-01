<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Registry\Saga\Cached;

use Gember\EventSourcing\Registry\Saga\SagaRegistry;
use Gember\EventSourcing\Resolver\Saga\SagaDefinition;
use Gember\EventSourcing\Util\Cache\CacheKey;
use JsonException;
use Override;
use Psr\SimpleCache\CacheInterface;
use TypeError;

/**
 * @phpstan-import-type SagaDefinitionPayload from SagaDefinition
 */
final readonly class CachedSagaRegistryDecorator implements SagaRegistry
{
    private const string CACHE_TYPE = 'registry.saga';

    public function __construct(
        private SagaRegistry $sagaRegistry,
        private CacheInterface $cache,
    ) {}

    #[Override]
    public function retrieve(string $sagaIdName): array
    {
        $cacheKey = $this->createCacheKey($sagaIdName);
        $cachedDefinitions = $this->cache->get($cacheKey);

        if (is_string($cachedDefinitions)) {
            try {
                /** @var list<SagaDefinitionPayload> $payloads */
                $payloads = json_decode($cachedDefinitions, true, flags: JSON_THROW_ON_ERROR);

                return array_map(fn($payload) => SagaDefinition::fromPayload($payload), $payloads);
            } catch (JsonException|TypeError) {
                // Unreadable entry: retrieve it again and overwrite it
            }
        }

        $definitions = $this->sagaRegistry->retrieve($sagaIdName);

        $this->cache->set($cacheKey, json_encode(
            array_map(fn($definition) => $definition->toPayload(), $definitions),
            JSON_THROW_ON_ERROR,
        ));

        return $definitions;
    }

    private function createCacheKey(string $sagaIdName): string
    {
        return CacheKey::create(self::CACHE_TYPE, $sagaIdName);
    }
}
