<?php

declare(strict_types=1);

namespace Gember\EventSourcing\EventStore\Rdbms;

use Gember\EventSourcing\UseCase\DomainEventEnvelope;
use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\NormalizedEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\UnresolvableEventNameException;
use Gember\EventSourcing\Util\Serialization\Serializer\SerializationFailedException;
use Gember\EventSourcing\Util\Serialization\Serializer\Serializer;

final readonly class RdbmsEventFactory
{
    public function __construct(
        private NormalizedEventNameResolver $eventNameResolver,
        private Serializer $serializer,
    ) {}

    /**
     * @throws SerializationFailedException
     * @throws UnresolvableEventNameException
     */
    public function createFromDomainEventEnvelope(DomainEventEnvelope $eventEnvelope): RdbmsEvent
    {
        return new RdbmsEvent(
            $eventEnvelope->eventId,
            $eventEnvelope->domainTags,
            $this->eventNameResolver->resolve($eventEnvelope->event::class),
            $this->serializer->serialize($eventEnvelope->event),
            [...$eventEnvelope->metadata],
            $eventEnvelope->appliedAt,
        );
    }
}
