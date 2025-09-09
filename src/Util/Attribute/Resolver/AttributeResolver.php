<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\Attribute\Resolver;

use ReflectionMethod;
use ReflectionProperty;

interface AttributeResolver
{
    /**
     * @template T of object
     *
     * @param class-string $className
     * @param class-string<T> $attributeClassName
     *
     * @return list<array{ReflectionProperty, T}>
     */
    public function getPropertiesWithAttribute(string $className, string $attributeClassName): array;

    /**
     * @template T of object
     *
     * @param class-string $className
     * @param class-string<T> $attributeClassName
     *
     * @return list<array{ReflectionMethod, T}>
     */
    public function getMethodsWithAttribute(string $className, string $attributeClassName): array;

    /**
     * @template T of object
     *
     * @param class-string $className
     * @param class-string<T> $attributeClassName
     *
     * @return list<T>
     */
    public function getAttributesForClass(string $className, string $attributeClassName): array;
}
