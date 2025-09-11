<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase\Default\EventSubscriber\Attribute;

use Gember\EventSourcing\Resolver\UseCase\Default\EventSubscriber\EventSubscriberResolver;
use Gember\EventSourcing\Resolver\UseCase\EventSubscriberDefinition;
use Gember\EventSourcing\UseCase\Attribute\DomainEventSubscriber;
use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Override;
use ReflectionMethod;
use ReflectionNamedType;

final readonly class AttributeEventSubscriberResolver implements EventSubscriberResolver
{
    public function __construct(
        private AttributeResolver $attributeResolver,
    ) {}

    #[Override]
    public function resolve(string $useCaseClassName): array
    {
        $methods = $this->attributeResolver->getMethodsWithAttribute(
            $useCaseClassName,
            DomainEventSubscriber::class,
        );

        $definition = [];

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

            /** @var class-string $eventClassName */
            $eventClassName = $firstParameter->getType()->getName();

            $definition[] = new EventSubscriberDefinition(
                $eventClassName,
                $reflectionMethod->getName(),
            );
        }

        return $definition;
    }
}
