<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Saga\Default\EventSubscriber\Attribute;

use Gember\EventSourcing\Resolver\Saga\Default\EventSubscriber\SagaEventSubscriberResolver;
use Gember\EventSourcing\Resolver\Saga\SagaEventSubscriberDefinition;
use Gember\EventSourcing\Saga\Attribute\SagaEventSubscriber;
use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Override;
use ReflectionMethod;
use ReflectionNamedType;

final readonly class AttributeSagaEventSubscriberResolver implements SagaEventSubscriberResolver
{
    public function __construct(
        private AttributeResolver $attributeResolver,
    ) {}

    #[Override]
    public function resolve(string $sagaClassName): array
    {
        $methods = $this->attributeResolver->getMethodsWithAttribute($sagaClassName, SagaEventSubscriber::class);

        $definition = [];

        /**
         * @var ReflectionMethod $reflectionMethod
         * @var SagaEventSubscriber $attribute
         */
        foreach ($methods as [$reflectionMethod, $attribute]) {
            $parameters = $reflectionMethod->getParameters();

            if ($parameters === []) {
                continue;
            }

            $firstParameter = $parameters[array_key_first($parameters)];

            if (!$firstParameter->getType() instanceof ReflectionNamedType) {
                continue;
            }

            /** @var class-string $sagaClassName */
            $sagaClassName = $firstParameter->getType()->getName();

            $definition[] = new SagaEventSubscriberDefinition(
                $sagaClassName,
                $reflectionMethod->getName(),
                $attribute->policy,
            );
        }

        return $definition;
    }
}
