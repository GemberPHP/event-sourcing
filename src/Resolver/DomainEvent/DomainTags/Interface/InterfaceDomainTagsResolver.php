<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\DomainTags\Interface;

use Gember\EventSourcing\UseCase\SpecifiedDomainTagsDomainEvent;
use Gember\EventSourcing\Resolver\DomainEvent\DomainTags\DomainTagsResolver;
use Gember\EventSourcing\Resolver\DomainEvent\DomainTags\UnresolvableDomainTagsException;
use Override;

final readonly class InterfaceDomainTagsResolver implements DomainTagsResolver
{
    #[Override]
    public function resolve(object $event): array
    {
        if (!is_subclass_of($event, SpecifiedDomainTagsDomainEvent::class)) {
            throw UnresolvableDomainTagsException::create(
                $event::class,
                'Event does not implement SpecifiedDomainTagsDomainEvent interface',
            );
        }

        /** @var SpecifiedDomainTagsDomainEvent $event */
        return $event->getDomainTags();
    }
}
