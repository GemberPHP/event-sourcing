<?php

declare(strict_types=1);

namespace Gember\EventSourcing\EventStore;

use Gember\EventSourcing\DomainContext\DomainEventEnvelope;

interface EventStore
{
    /**
     * @throws EventStoreFailedException
     * @throws NoEventsForDomainIdsException
     *
     * @return list<DomainEventEnvelope>
     */
    public function load(StreamQuery $streamQuery): array;

    /**
     * @throws OptimisticLockException
     * @throws EventStoreFailedException
     */
    public function append(StreamQuery $streamQuery, ?string $lastEventId, DomainEventEnvelope ...$eventEnvelopes): void;
}
