<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainContext\SubscriberMethodForEvent;

use Gember\EventSourcing\DomainContext\EventSourcedDomainContext;

/**
 * Resolves the subscribing method name (if present) for a domain event within a domain context.
 */
interface SubscriberMethodForEventResolver
{
    /**
     * @param class-string<EventSourcedDomainContext> $domainContextClassName
     * @param class-string $eventClassName
     */
    public function resolve(
        string $domainContextClassName,
        string $eventClassName,
    ): ?string;
}
