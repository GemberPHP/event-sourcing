<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase\SubscribedEvents\Attribute;

use Gember\EventSourcing\UseCase\Attribute\DomainEventSubscriber;
use Gember\EventSourcing\Resolver\UseCase\SubscribedEvents\SubscribedEventsResolver;
use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Override;
use ReflectionMethod;
use ReflectionNamedType;

final readonly class AttributeSubscribedEventsResolver implements SubscribedEventsResolver
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

        $eventClassNames = [];

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
            $eventClassNames[] = $eventClassName;
        }

        return $eventClassNames;
    }
}
