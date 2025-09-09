<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase\SubscriberMethodForEvent\Attribute;

use Gember\EventSourcing\UseCase\Attribute\DomainEventSubscriber;
use Gember\EventSourcing\Resolver\UseCase\SubscriberMethodForEvent\SubscriberMethodForEventResolver;
use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Override;
use ReflectionMethod;
use ReflectionNamedType;

final readonly class AttributeSubscriberMethodForEventResolver implements SubscriberMethodForEventResolver
{
    public function __construct(
        private AttributeResolver $attributeResolver,
    ) {}

    #[Override]
    public function resolve(string $useCaseClassName, string $eventClassName): ?string
    {
        $methods = $this->attributeResolver->getMethodsWithAttribute(
            $useCaseClassName,
            DomainEventSubscriber::class,
        );

        /** @var ReflectionMethod $reflectionMethod */
        foreach ($methods as [$reflectionMethod]) {
            $parameters = $reflectionMethod->getParameters();

            if ($parameters === []) {
                continue;
            }

            $firstParameter = $parameters[array_key_first($parameters)];

            if (!$firstParameter->getType() instanceof ReflectionNamedType) {
                continue;
            }

            if ($firstParameter->getType()->getName() !== $eventClassName) {
                continue;
            }

            return $reflectionMethod->getName();
        }

        return null;
    }
}
