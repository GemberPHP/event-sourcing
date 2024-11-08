<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainContext\SubscribedEvents\Attribute;

use Gember\EventSourcing\DomainContext\Attribute\DomainEventSubscriber;
use Gember\EventSourcing\Resolver\DomainContext\SubscribedEvents\SubscribedEventsResolver;
use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Override;

final readonly class AttributeSubscribedEventsResolver implements SubscribedEventsResolver
{
    public function __construct(
        private AttributeResolver $attributeResolver,
    ) {}

    #[Override]
    public function resolve(string $domainContextClassName): array
    {
        $methods = $this->attributeResolver->getMethodsWithAttribute(
            $domainContextClassName,
            DomainEventSubscriber::class,
        );

        $eventClassNames = [];
        foreach ($methods as $method) {
            if ($method->parameters === []) {
                continue;
            }

            $firstParameter = $method->parameters[array_key_first($method->parameters)];

            if ($firstParameter->type === null) {
                continue;
            }

            $eventClassNames[] = $firstParameter->type;
        }

        return $eventClassNames;
    }
}
