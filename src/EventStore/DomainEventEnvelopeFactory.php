<?php

declare(strict_types=1);

namespace Gember\EventSourcing\EventStore;

use Gember\EventSourcing\UseCase\DomainEventEnvelope;
use Gember\EventSourcing\UseCase\Metadata;
use Gember\EventSourcing\Resolver\DomainEvent\DomainTags\DomainTagsResolver;
use Gember\EventSourcing\Resolver\DomainEvent\DomainTags\UnresolvableDomainTagsException;
use Gember\EventSourcing\Util\Generator\Identity\IdentityGenerator;
use Gember\EventSourcing\Util\Time\Clock\Clock;

final readonly class DomainEventEnvelopeFactory
{
    public function __construct(
        private DomainTagsResolver $domainTagsResolver,
        private IdentityGenerator $identityGenerator,
        private Clock $clock,
    ) {}

    /**
     * @throws UnresolvableDomainTagsException
     */
    public function createFromAppliedEvent(object $appliedEvent): DomainEventEnvelope
    {
        return new DomainEventEnvelope(
            $this->identityGenerator->generate(),
            array_map(
                fn($domainTag) => (string) $domainTag,
                $this->domainTagsResolver->resolve($appliedEvent),
            ),
            $appliedEvent,
            new Metadata(),
            $this->clock->now(),
        );
    }
}
