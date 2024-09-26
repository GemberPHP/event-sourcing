<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainContext\SubscriberMethodForEvent\Attribute;

use Gember\EventSourcing\DomainContext\Attribute\DomainEventSubscriber;
use Gember\EventSourcing\DomainContext\EventSourcedDomainContext;
use Gember\EventSourcing\Resolver\DomainContext\SubscriberMethodForEvent\SubscriberMethodForEventResolver;
use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Override;

/**
 * @template T of EventSourcedDomainContext
 *
 * @implements SubscriberMethodForEventResolver<T>
 */
final readonly class AttributeSubscriberMethodForEventResolver implements SubscriberMethodForEventResolver
{
    public function __construct(
        private AttributeResolver $attributeResolver,
    ) {}

    #[Override]
    public function resolve(string $domainContextClassName, string $eventClassName): ?string
    {
        $methods = $this->attributeResolver->getMethodsWithAttribute(
            $domainContextClassName,
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
