<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\EventStore\Rdbms;

use Gember\DependencyContracts\EventStore\Rdbms\OptimisticLockException;
use Gember\DependencyContracts\EventStore\Rdbms\RdbmsEvent;
use Gember\DependencyContracts\EventStore\Rdbms\RdbmsEventStoreRepository;
use Override;
use Exception;

final class TestRdbmsEventStoreRepository implements RdbmsEventStoreRepository
{
    /**
     * @var list<RdbmsEvent>
     */
    public array $events = [];
    public ?Exception $throwException = null;
    public ?int $eventCountPersisted = null;

    #[Override]
    public function getEvents(array $domainTags, array $eventNames, ?string $afterEventId = null): array
    {
        if ($this->throwException !== null) {
            throw $this->throwException;
        }

        return $this->events;
    }

    #[Override]
    public function countEvents(array $domainTags, array $eventNames): int
    {
        if ($this->throwException !== null) {
            throw $this->throwException;
        }

        return $this->eventCountPersisted ?? count($this->events);
    }

    #[Override]
    public function saveEvents(array $domainTags, array $eventNames, int $expectedEventCount, array $events): void
    {
        if ($this->eventCountPersisted !== null && $this->eventCountPersisted !== $expectedEventCount) {
            throw OptimisticLockException::create();
        }

        if ($this->throwException !== null) {
            throw $this->throwException;
        }

        $this->events = $events;
    }
}
