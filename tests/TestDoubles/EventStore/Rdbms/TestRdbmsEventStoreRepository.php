<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\EventStore\Rdbms;

use Gember\EventSourcing\EventStore\Rdbms\RdbmsEvent;
use Gember\EventSourcing\EventStore\Rdbms\RdbmsEventStoreRepository;
use Override;
use Exception;

final class TestRdbmsEventStoreRepository implements RdbmsEventStoreRepository
{
    /**
     * @var list<RdbmsEvent>
     */
    public array $events = [];
    public ?Exception $throwException = null;
    public ?string $lastEventIdPersisted = null;

    #[Override]
    public function getEvents(array $domainTags, array $eventNames): array
    {
        if ($this->throwException !== null) {
            throw $this->throwException;
        }

        return $this->events;
    }

    #[Override]
    public function getLastEventIdPersisted(array $domainTags, array $eventNames): ?string
    {
        return $this->lastEventIdPersisted;
    }

    #[Override]
    public function saveEvents(array $events): void
    {
        if ($this->throwException !== null) {
            throw $this->throwException;
        }

        $this->events = $events;
    }
}
