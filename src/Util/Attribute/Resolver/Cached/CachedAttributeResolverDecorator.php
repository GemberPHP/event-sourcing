<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\Attribute\Resolver\Cached;

use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Gember\EventSourcing\Util\Attribute\Resolver\Method;
use Gember\EventSourcing\Util\Cache\Cache;
use Gember\EventSourcing\Util\Cache\CacheException;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\FriendlyClassNamer;
use JsonException;
use Override;

/**
 * @phpstan-import-type MethodPayload from Method
 */
final readonly class CachedAttributeResolverDecorator implements AttributeResolver
{
    private const string CACHE_KEY_PROPERTIES = 'gember.attribute-resolver.properties.%s';
    private const string CACHE_KEY_CLASS_ATTRIBUTES = 'gember.attribute-resolver.class-attributes.%s';
    private const string CACHE_KEY_METHODS = 'gember.attribute-resolver.methods.%s';

    /**
     * @param Cache<string> $cache
     */
    public function __construct(
        private AttributeResolver $attributeResolver,
        private FriendlyClassNamer $friendlyClassNamer,
        private Cache $cache,
    ) {}

    /**
     * @throws CacheException
     * @throws JsonException
     */
    #[Override]
    public function getPropertyNamesWithAttribute(string $className, string $attributeClassName): array
    {
        $cacheKey = $this->createCacheKey(self::CACHE_KEY_PROPERTIES, $className, $attributeClassName);

        if (!$this->cache->has($cacheKey)) {
            $properties = $this->attributeResolver->getPropertyNamesWithAttribute($className, $attributeClassName);

            $this->cache->set($cacheKey, json_encode($properties, JSON_THROW_ON_ERROR));

            return $properties;
        }

        return (array) json_decode($this->cache->get($cacheKey), true, flags: JSON_THROW_ON_ERROR); // @phpstan-ignore-line
    }

    #[Override]
    public function getMethodsWithAttribute(string $className, string $attributeClassName): array
    {
        $cacheKey = $this->createCacheKey(self::CACHE_KEY_METHODS, $className, $attributeClassName);

        if (!$this->cache->has($cacheKey)) {
            $methods = $this->attributeResolver->getMethodsWithAttribute($className, $attributeClassName);

            $this->cache->set($cacheKey, json_encode($methods, JSON_THROW_ON_ERROR));

            return $methods;
        }

        /** @var list<MethodPayload> $data */
        $data = (array) json_decode($this->cache->get($cacheKey), true, flags: JSON_THROW_ON_ERROR);

        return array_map(fn($item) => Method::fromArray($item), $data);
    }

    /**
     * @throws CacheException
     * @throws JsonException
     */
    #[Override]
    public function getAttributesForClass(string $className, string $attributeClassName): array
    {
        $cacheKey = $this->createCacheKey(self::CACHE_KEY_CLASS_ATTRIBUTES, $className, $attributeClassName);

        if (!$this->cache->has($cacheKey)) {
            $attributes = $this->attributeResolver->getAttributesForClass($className, $attributeClassName);

            $serializedAttributes = array_map(fn($attribute) => serialize($attribute), $attributes);

            $this->cache->set(
                $cacheKey,
                json_encode($serializedAttributes, JSON_THROW_ON_ERROR),
            );

            return $attributes;
        }

        /** @var list<string> $serializedAttributes */
        $serializedAttributes = (array) json_decode(
            $this->cache->get($cacheKey),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        return array_map(fn($serializedAttribute) => unserialize($serializedAttribute), $serializedAttributes); // @phpstan-ignore-line
    }

    /**
     * @param class-string ...$classNames
     */
    private function createCacheKey(string $prefix, string ...$classNames): string
    {
        return sprintf(
            $prefix,
            implode('.', array_map(
                fn($className) => $this->friendlyClassNamer->createFriendlyClassName($className),
                $classNames,
            )),
        );
    }
}
