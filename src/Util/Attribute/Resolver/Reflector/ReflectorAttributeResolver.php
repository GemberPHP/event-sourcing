<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\Attribute\Resolver\Reflector;

use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use ReflectionClass;
use ReflectionException;
use Override;

final readonly class ReflectorAttributeResolver implements AttributeResolver
{
    /**
     * @throws ReflectionException
     */
    #[Override]
    public function getPropertiesWithAttribute(string $className, string $attributeClassName): array
    {
        $reflectionClass = new ReflectionClass($className);

        $properties = [];
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $attributes = $reflectionProperty->getAttributes($attributeClassName);

            if ($attributes === []) {
                continue;
            }

            $properties[] = [$reflectionProperty, $attributes[array_key_first($attributes)]->newInstance()];
        }

        return $properties;
    }

    /**
     * @throws ReflectionException
     */
    #[Override]
    public function getMethodsWithAttribute(string $className, string $attributeClassName): array
    {
        $reflectionClass = new ReflectionClass($className);

        $methods = [];
        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            $attributes = $reflectionMethod->getAttributes($attributeClassName);

            if ($attributes === []) {
                continue;
            }

            $methods[] = [$reflectionMethod, $attributes[array_key_first($attributes)]->newInstance()];
        }

        return $methods;
    }

    /**
     * @throws ReflectionException
     */
    #[Override]
    public function getAttributesForClass(string $className, string $attributeClassName): array
    {
        $reflectionClass = new ReflectionClass($className);

        return array_map(
            fn($attribute) => $attribute->newInstance(),
            $reflectionClass->getAttributes($attributeClassName),
        );
    }
}
