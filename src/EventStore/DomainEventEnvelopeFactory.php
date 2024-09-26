<?php

declare(strict_types=1);

namespace Gember\EventSourcing\EventStore;

use Gember\EventSourcing\DomainContext\DomainEventEnvelope;
use Gember\EventSourcing\DomainContext\Metadata;
use Gember\EventSourcing\Resolver\DomainEvent\DomainIds\DomainIdsResolver;
use Gember\EventSourcing\Resolver\DomainEvent\DomainIds\UnresolvableDomainIdsException;
use Gember\EventSourcing\Util\Generator\Identity\IdentityGenerator;
use Gember\EventSourcing\Util\Time\Clock\Clock;

final readonly class DomainEventEnvelopeFactory
{
    public function __construct(
        private DomainIdsResolver $eventDomainIdsResolver,
        private IdentityGenerator $identityGenerator,
        private Clock $clock,
    ) {}

    /**
     * @throws UnresolvableDomainIdsException
     */
    public function createFromAppliedEvent(object $appliedEvent): DomainEventEnvelope
    {
        return new DomainEventEnvelope(
            $this->identityGenerator->generate(),
            array_map(
                fn($domainId) => (string) $domainId,
                $this->eventDomainIdsResolver->resolve($appliedEvent),
            ),
            $appliedEvent,
            new Metadata(),
            $this->clock->now(),
        );
    }
}
