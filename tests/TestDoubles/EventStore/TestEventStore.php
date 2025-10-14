<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\EventStore;

use Exception;
use Gember\EventSourcing\EventStore\EventStore;
use Gember\EventSourcing\EventStore\StreamQuery;
use Gember\EventSourcing\UseCase\DomainEventEnvelope;

/**
 * @internal
 */
final class TestEventStore implements EventStore
{
    /**
     * @param list<DomainEventEnvelope> $envelopesToReturn
     * @param list<DomainEventEnvelope> $lastAppendEventEnvelopes
     */
    public function __construct(
        public bool $loadWasCalled = false,
        public ?StreamQuery $lastLoadStreamQuery = null,
        public array $envelopesToReturn = [],
        public ?Exception $loadShouldThrow = null,
        public bool $appendWasCalled = false,
        public ?StreamQuery $lastAppendStreamQuery = null,
        public ?string $lastAppendLastEventId = null,
        public array $lastAppendEventEnvelopes = [],
        public ?Exception $appendShouldThrow = null,
    ) {}

    public function load(StreamQuery $streamQuery): array
    {
        $this->loadWasCalled = true;
        $this->lastLoadStreamQuery = $streamQuery;

        if ($this->loadShouldThrow !== null) {
            throw $this->loadShouldThrow;
        }

        return $this->envelopesToReturn;
    }

    public function append(StreamQuery $streamQuery, ?string $lastEventId, DomainEventEnvelope ...$eventEnvelopes): void
    {
        $this->appendWasCalled = true;
        $this->lastAppendStreamQuery = $streamQuery;
        $this->lastAppendLastEventId = $lastEventId;
        $this->lastAppendEventEnvelopes = array_values($eventEnvelopes);

        if ($this->appendShouldThrow !== null) {
            throw $this->appendShouldThrow;
        }
    }
}
