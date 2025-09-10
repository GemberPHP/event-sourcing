<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\Stacked;

use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\EventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\UnresolvableEventNameException;
use Override;

final readonly class StackedEventNameResolver implements EventNameResolver
{
    /**
     * @param iterable<EventNameResolver> $eventNameResolvers
     */
    public function __construct(
        private iterable $eventNameResolvers,
        private EventNameResolver $fallbackEventNameResolver,
    ) {}

    #[Override]
    public function resolve(string $eventClassName): string
    {
        foreach ($this->eventNameResolvers as $eventNameResolver) {
            try {
                return $eventNameResolver->resolve($eventClassName);
            } catch (UnresolvableEventNameException) {
                continue;
            }
        }

        return $this->fallbackEventNameResolver->resolve($eventClassName);
    }
}
