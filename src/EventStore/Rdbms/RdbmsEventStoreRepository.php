<?php

declare(strict_types=1);

namespace Gember\EventSourcing\EventStore\Rdbms;

use Stringable;

interface RdbmsEventStoreRepository
{
    /**
     * @param list<string|Stringable> $domainTags
     * @param list<string> $eventNames
     *
     * @return list<RdbmsEvent>
     */
    public function getEvents(array $domainTags, array $eventNames): array;

    /**
     * @param list<string|Stringable> $domainTags
     * @param list<string> $eventNames
     */
    public function getLastEventIdPersisted(array $domainTags, array $eventNames): ?string;

    /**
     * @param list<RdbmsEvent> $events
     */
    public function saveEvents(array $events): void;
}
