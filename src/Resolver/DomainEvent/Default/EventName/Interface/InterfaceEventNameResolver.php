<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\Interface;

use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\EventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\UnresolvableEventNameException;
use Gember\EventSourcing\UseCase\NamedDomainEvent;
use Override;

/**
 * Resolve event name by interface NamedDomainEvent.
 */
final readonly class InterfaceEventNameResolver implements EventNameResolver
{
    #[Override]
    public function resolve(string $eventClassName): string
    {
        if (!is_subclass_of($eventClassName, NamedDomainEvent::class)) {
            throw UnresolvableEventNameException::create(
                $eventClassName,
                'Event does not implement NamedDomainEvent interface',
            );
        }

        /* @var class-string<NamedDomainEvent> $eventClassName */
        return $eventClassName::getName();
    }
}
