<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\Interface;

use Gember\EventSourcing\UseCase\NamedDomainEvent;
use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\NormalizedEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\UnresolvableEventNameException;
use Override;

final readonly class InterfaceNormalizedEventNameResolver implements NormalizedEventNameResolver
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
