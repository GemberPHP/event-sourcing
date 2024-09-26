<?php

declare(strict_types=1);

namespace Gember\EventSourcing\EventStore\Rdbms;

use Stringable;

interface RdbmsEventStoreRepository
{
    /**
     * @param list<string|Stringable> $domainIds
     * @param list<string> $eventNames
     *
     * @return list<RdbmsEvent>
     */
    public function getEvents(array $domainIds, array $eventNames): array;

    /**
     * @param list<string|Stringable> $domainIds
     * @param list<string> $eventNames
     */
    public function getLastEventIdPersisted(array $domainIds, array $eventNames): ?string;

    /**
     * @param list<RdbmsEvent> $events
     */
    public function saveEvents(array $events): void;
}
