<?php

declare(strict_types=1);

namespace Gember\EventSourcing\EventStore\Rdbms;

use Gember\EventSourcing\UseCase\DomainEventEnvelope;
use Gember\EventSourcing\UseCase\Metadata;
use Gember\EventSourcing\Registry\EventNotRegisteredException;
use Gember\EventSourcing\Registry\EventRegistry;
use Gember\EventSourcing\Util\Serialization\Serializer\SerializationFailedException;
use Gember\EventSourcing\Util\Serialization\Serializer\Serializer;

final readonly class RdbmsDomainEventEnvelopeFactory
{
    public function __construct(
        private Serializer $serializer,
        private EventRegistry $eventRegistry,
    ) {}

    /**
     * @throws SerializationFailedException
     * @throws EventNotRegisteredException
     */
    public function createFromRdbmsEvent(RdbmsEvent $row): DomainEventEnvelope
    {
        return new DomainEventEnvelope(
            $row->eventId,
            $row->domainIds,
            $this->serializer->deserialize($row->payload, $this->eventRegistry->retrieve($row->eventName)),
            new Metadata($row->metadata),
            $row->appliedAt,
        );
    }
}
