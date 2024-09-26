<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName;

/**
 * Resolve normalized event name from a domain event.
 * The event name is used to identify the event in the event store (persisted).
 */
interface NormalizedEventNameResolver
{
    /**
     * @param class-string $eventClassName
     *
     * @throws UnresolvableEventNameException
     */
    public function resolve(string $eventClassName): string;
}
