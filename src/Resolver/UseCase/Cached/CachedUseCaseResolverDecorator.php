<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase\Cached;

use Gember\EventSourcing\Resolver\UseCase\UseCaseDefinition;
use Gember\EventSourcing\Resolver\UseCase\UseCaseResolver;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\FriendlyClassNamer;
use Psr\SimpleCache\CacheInterface;
use Override;
use JsonException;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @phpstan-import-type UseCaseDefinitionPayload from UseCaseDefinition
 */
final readonly class CachedUseCaseResolverDecorator implements UseCaseResolver
{
    private const string CACHE_KEY = 'gember.resolver.use_case.%s';

    public function __construct(
        private UseCaseResolver $useCaseResolver,
        private CacheInterface $cache,
        private FriendlyClassNamer $friendlyClassNamer,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    #[Override]
    public function resolve(string $useCaseClassName): UseCaseDefinition
    {
        $cacheKey = $this->createCacheKey($useCaseClassName);

        if (!$this->cache->has($cacheKey)) {
            $definition = $this->useCaseResolver->resolve($useCaseClassName);

            $this->cache->set($cacheKey, json_encode($definition->toPayload(), JSON_THROW_ON_ERROR));

            return $definition;
        }

        /** @var string $cachedDefinition */
        $cachedDefinition = $this->cache->get($cacheKey);

        /** @var UseCaseDefinitionPayload $payload */
        $payload = json_decode($cachedDefinition, true, flags: JSON_THROW_ON_ERROR);

        return UseCaseDefinition::fromPayload($payload);
    }

    /**
     * @param class-string $eventClassName
     */
    private function createCacheKey(string $eventClassName): string
    {
        return sprintf(self::CACHE_KEY, $this->friendlyClassNamer->createFriendlyClassName($eventClassName));
    }
}
