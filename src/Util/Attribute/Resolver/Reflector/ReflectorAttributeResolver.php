<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\Attribute\Resolver\Reflector;

use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Gember\EventSourcing\Util\Attribute\Resolver\Method;
use Gember\EventSourcing\Util\Attribute\Resolver\Parameter;
use ReflectionClass;
use ReflectionException;
use Override;
use ReflectionNamedType;

final readonly class ReflectorAttributeResolver implements AttributeResolver
{
    /**
     * @throws ReflectionException
     */
    #[Override]
    public function getPropertyNamesWithAttribute(string $className, string $attributeClassName): array
    {
        $reflectionClass = new ReflectionClass($className);

        $properties = [];
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $attributes = $reflectionProperty->getAttributes($attributeClassName);

            if ($attributes === []) {
                continue;
            }

            $properties[] = $reflectionProperty->getName();
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

            $parameters = [];
            foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
                $type = null;
                if ($reflectionParameter->getType() instanceof ReflectionNamedType) {
                    /** @var class-string $type */
                    $type = $reflectionParameter->getType()->getName();
                }

                $parameters[] = new Parameter($reflectionParameter->getName(), $type);
            }

            $methods[] = new Method($reflectionMethod->getName(), $parameters);
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
