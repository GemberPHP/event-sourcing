<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainContext\SubscribedEvents;

use Gember\EventSourcing\DomainContext\EventSourcedDomainContext;

/**
 * Resolve all used domain events inside a domain context.
 * A domain event is used in a domain context when it is subscribed to a domain event.
 *
 * When loading all events required for a certain domain context from the event store,
 * all events subscribed on should be filtered on, to be able te reconstitute the domain context.
 */
interface SubscribedEventsResolver
{
    /**
     * Returns a list of subscribed domain events (FQCN) inside a domain context.
     *
     * @param class-string<EventSourcedDomainContext> $domainContextClassName
     *
     * @return list<class-string>
     */
    public function resolve(string $domainContextClassName): array;
}
