<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase\Cached;

use Gember\EventSourcing\Resolver\UseCase\UseCaseDefinition;
use Gember\EventSourcing\Resolver\UseCase\UseCaseResolver;
use Gember\EventSourcing\Util\Cache\CacheKey;
use Psr\SimpleCache\CacheInterface;
use Override;
use JsonException;
use TypeError;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @phpstan-import-type UseCaseDefinitionPayload from UseCaseDefinition
 */
final readonly class CachedUseCaseResolverDecorator implements UseCaseResolver
{
    private const string CACHE_TYPE = 'resolver.use_case';

    public function __construct(
        private UseCaseResolver $useCaseResolver,
        private CacheInterface $cache,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    #[Override]
    public function resolve(string $useCaseClassName): UseCaseDefinition
    {
        $cacheKey = $this->createCacheKey($useCaseClassName);
        $cachedDefinition = $this->cache->get($cacheKey);

        if (is_string($cachedDefinition)) {
            try {
                /** @var UseCaseDefinitionPayload $payload */
                $payload = json_decode($cachedDefinition, true, flags: JSON_THROW_ON_ERROR);

                return UseCaseDefinition::fromPayload($payload);
            } catch (JsonException|TypeError) {
                // Unreadable entry: resolve it again and overwrite it
            }
        }

        $definition = $this->useCaseResolver->resolve($useCaseClassName);

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
