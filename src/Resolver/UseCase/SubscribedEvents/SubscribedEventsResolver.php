<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase\SubscribedEvents;

use Gember\EventSourcing\UseCase\EventSourcedUseCase;

/**
 * Resolve all used domain events inside a use case.
 * A domain event is used in a use case when it is subscribed to a domain event.
 *
 * When loading all events required for a certain use case from the event store,
 * all events subscribed on should be filtered on, to be able te reconstitute the use case.
 */
interface SubscribedEventsResolver
{
    /**
     * Returns a list of subscribed domain events (FQCN) inside a use case.
     *
     * @param class-string<EventSourcedUseCase> $useCaseClassName
     *
     * @return list<class-string>
     */
    public function resolve(string $useCaseClassName): array;
}
