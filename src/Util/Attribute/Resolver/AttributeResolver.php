<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\Attribute\Resolver;

interface AttributeResolver
{
    /**
     * @param class-string $className
     * @param class-string<object> $attributeClassName
     *
     * @return list<string>
     */
    public function getPropertyNamesWithAttribute(string $className, string $attributeClassName): array;

    /**
     * @param class-string $className
     * @param class-string<object> $attributeClassName
     *
     * @return list<Method>
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
