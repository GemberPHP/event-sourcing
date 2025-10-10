<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Saga\Default\SagaName\Attribute;

use Gember\EventSourcing\Resolver\Saga\Default\SagaName\SagaNameResolver;
use Gember\EventSourcing\Resolver\Saga\Default\SagaName\UnresolvableSagaNameException;
use Gember\EventSourcing\Saga\Attribute\Saga;
use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Override;

/**
 * Resolve saga name by attribute #[Saga].
 */
final readonly class AttributeSagaNameResolver implements SagaNameResolver
{
    public function __construct(
        private AttributeResolver $attributeResolver,
    ) {}

    #[Override]
    public function resolve(string $sagaClassName): string
    {
        $attributes = $this->attributeResolver->getAttributesForClass($sagaClassName, Saga::class);

        if ($attributes === []) {
            throw UnresolvableSagaNameException::create(
                $sagaClassName,
                'Saga does not contain #[Saga] attribute',
            );
        }

        return $attributes[array_key_first($attributes)]->name;
    }
}
