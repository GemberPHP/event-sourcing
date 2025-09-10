<?php

declare(strict_types=1);

namespace Gember\EventSourcing\EventStore;

use Gember\DependencyContracts\Util\Generator\Identity\IdentityGenerator;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagValueHelper;
use Gember\EventSourcing\Resolver\DomainEvent\DomainEventResolver;
use Gember\EventSourcing\UseCase\DomainEventEnvelope;
use Gember\EventSourcing\UseCase\Metadata;
use Gember\EventSourcing\Util\Time\Clock\Clock;

final readonly class DomainEventEnvelopeFactory
{
    public function __construct(
        private DomainEventResolver $domainEventResolver,
        private IdentityGenerator $identityGenerator,
        private Clock $clock,
    ) {}

    public function createFromAppliedEvent(object $appliedEvent): DomainEventEnvelope
    {
        return new DomainEventEnvelope(
            $this->identityGenerator->generate(),
            DomainTagValueHelper::getDomainTagValues(
                $appliedEvent,
                $this->domainEventResolver->resolve($appliedEvent::class)->domainTags,
            ),
            $appliedEvent,
            new Metadata(),
            $this->clock->now(),
        );
    }
}
