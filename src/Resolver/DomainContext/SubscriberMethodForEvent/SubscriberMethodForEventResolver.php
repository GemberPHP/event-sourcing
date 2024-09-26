<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainContext\SubscriberMethodForEvent;

use Gember\EventSourcing\DomainContext\EventSourcedDomainContext;

/**
 * Resolves the subscribing method name (if present) for a domain event within a domain context.
 *
 * @template T of EventSourcedDomainContext
 */
interface SubscriberMethodForEventResolver
{
    /**
     * @param class-string<EventSourcedDomainContext<T>> $domainContextClassName
     * @param class-string $eventClassName
     */
    public function resolve(
        string $domainContextClassName,
        string $eventClassName,
    ): ?string;
}
