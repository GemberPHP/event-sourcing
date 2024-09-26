<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\DomainIds\Interface;

use Gember\EventSourcing\DomainContext\SpecifiedDomainIdsDomainEvent;
use Gember\EventSourcing\Resolver\DomainEvent\DomainIds\DomainIdsResolver;
use Gember\EventSourcing\Resolver\DomainEvent\DomainIds\UnresolvableDomainIdsException;
use Override;

final readonly class InterfaceDomainIdsResolver implements DomainIdsResolver
{
    #[Override]
    public function resolve(object $event): array
    {
        if (!is_subclass_of($event, SpecifiedDomainIdsDomainEvent::class)) {
            throw UnresolvableDomainIdsException::create(
                $event::class,
                'Event does not implement SpecifiedDomainIdsDomainEvent interface',
            );
        }

        /** @var SpecifiedDomainIdsDomainEvent $event */
        return $event->getDomainIds();
    }
}
