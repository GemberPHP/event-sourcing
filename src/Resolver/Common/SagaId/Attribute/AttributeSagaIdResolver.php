<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Common\SagaId\Attribute;

use Gember\EventSourcing\Resolver\Common\SagaId\SagaIdDefinition;
use Gember\EventSourcing\Resolver\Common\SagaId\SagaIdResolver;
use Gember\EventSourcing\Saga\Attribute\SagaId;
use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Override;
use ReflectionProperty;

final readonly class AttributeSagaIdResolver implements SagaIdResolver
{
    public function __construct(
        private AttributeResolver $attributeResolver,
    ) {}

    #[Override]
    public function resolve(string $className): array
    {
        $properties = $this->attributeResolver->getPropertiesWithAttribute($className, SagaId::class);

        $sagaIds = [];

        if ($properties === []) {
            return [];
        }

        /**
         * @var ReflectionProperty $reflectionProperty
         * @var SagaId $attribute
         */
        foreach ($properties as [$reflectionProperty, $attribute]) {
            $sagaIds[] = new SagaIdDefinition(
                $attribute->name ?? $reflectionProperty->getName(),
            );
        }

        return $sagaIds;
    }
}
