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
     * @param list<list<DomainEventEnvelope>|Exception> $loadResults
     * @param list<DomainEventEnvelope> $lastAppendEventEnvelopes
     */
    public function __construct(
        public bool $loadWasCalled = false,
        public ?StreamQuery $lastLoadStreamQuery = null,
        public array $envelopesToReturn = [],
        public array $loadResults = [],
        public ?Exception $loadShouldThrow = null,
        public bool $appendWasCalled = false,
        public ?StreamQuery $lastAppendStreamQuery = null,
        public ?int $lastAppendExpectedEventCount = null,
        public array $lastAppendEventEnvelopes = [],
        public ?Exception $appendShouldThrow = null,
        private int $loadCallCount = 0,
        public int $countToReturn = 0,
        public int $countCallCount = 0,
    ) {}

    public function load(StreamQuery $streamQuery): array
    {
        $this->loadWasCalled = true;
        $this->lastLoadStreamQuery = $streamQuery;

        if ($this->loadResults !== []) {
            $result = $this->loadResults[$this->loadCallCount++] ?? $this->envelopesToReturn;

            if ($result instanceof Exception) {
                throw $result;
            }

            return $result;
        }

        if ($this->loadShouldThrow !== null) {
            throw $this->loadShouldThrow;
        }

        return $this->envelopesToReturn;
    }

    public function count(StreamQuery $streamQuery): int
    {
        ++$this->countCallCount;

        return $this->countToReturn;
    }

    public function append(StreamQuery $streamQuery, int $expectedEventCount, DomainEventEnvelope ...$eventEnvelopes): void
    {
        $this->appendWasCalled = true;
        $this->lastAppendStreamQuery = $streamQuery;
        $this->lastAppendExpectedEventCount = $expectedEventCount;
        $this->lastAppendEventEnvelopes = array_values($eventEnvelopes);

        if ($this->appendShouldThrow !== null) {
            throw $this->appendShouldThrow;
        }
    }
}
