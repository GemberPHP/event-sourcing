<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase\SubscriberMethodForEvent\Attribute;

use Gember\EventSourcing\UseCase\Attribute\DomainEventSubscriber;
use Gember\EventSourcing\Resolver\UseCase\SubscriberMethodForEvent\SubscriberMethodForEventResolver;
use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Override;

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

        foreach ($methods as $method) {
            if ($method->parameters === []) {
                continue;
            }

            $firstParameter = $method->parameters[array_key_first($method->parameters)];

            if ($firstParameter->type !== $eventClassName) {
                continue;
            }

            return $method->name;
        }

        return null;
    }
}
