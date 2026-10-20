<?php

declare(strict_types=1);

namespace Gember\EventSourcing\EventStore;

use Gember\DependencyContracts\EventStore\Rdbms\OptimisticLockException;
use Gember\EventSourcing\UseCase\DomainEventEnvelope;

interface EventStore
{
    /**
     * @throws EventStoreFailedException
     * @throws NoEventsForDomainTagsException
     *
     * @return list<DomainEventEnvelope>
     */
    public function load(StreamQuery $streamQuery): array;

    /**
     * Counts the events matching the stream query (ignoring afterEventId): the number of events load() returns.
     *
     * @throws EventStoreFailedException
     */
    public function count(StreamQuery $streamQuery): int;

    /**
     * Appends events, provided the number of events matching the stream query still equals $expectedEventCount:
     * the number of matching events the caller based its decision on.
     *
     * @throws OptimisticLockException
     * @throws EventStoreFailedException
     */
    public function append(StreamQuery $streamQuery, int $expectedEventCount, DomainEventEnvelope ...$eventEnvelopes): void;
}
