<?php

declare(strict_types=1);

namespace Gember\EventSourcing\EventStore\Rdbms;

use Gember\DependencyContracts\EventStore\Rdbms\RdbmsEvent;
use Gember\EventSourcing\Resolver\DomainEvent\DomainEventResolver;
use Gember\EventSourcing\UseCase\DomainEventEnvelope;
use Gember\DependencyContracts\Util\Serialization\Serializer\SerializationFailedException;
use Gember\DependencyContracts\Util\Serialization\Serializer\Serializer;

final readonly class RdbmsEventFactory
{
    public function __construct(
        private DomainEventResolver $domainEventResolver,
        private Serializer $serializer,
    ) {}

    /**
     * @throws SerializationFailedException
     */
    public function createFromDomainEventEnvelope(DomainEventEnvelope $eventEnvelope): RdbmsEvent
    {
        $domainEventDefinition = $this->domainEventResolver->resolve($eventEnvelope->event::class);

        return new RdbmsEvent(
            $eventEnvelope->eventId,
            $eventEnvelope->domainTags,
            $domainEventDefinition->eventName,
            $this->serializer->serialize($eventEnvelope->event),
            [...$eventEnvelope->metadata],
            $eventEnvelope->appliedAt,
        );
    }
}
