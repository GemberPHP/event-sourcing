<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Snapshot;

final readonly class SnapshotEnvelope
{
    /**
     * @param list<string> $domainTags
     * @param list<string> $eventNames The subscribed event classes, plus an entry for the use case class and
     *                                 snapshot version (see SnapshotStore::load())
     */
    public function __construct(
        public array $domainTags,
        public array $eventNames,
        public string $lastEventId,
        public int $eventCount,
        public string $state,
    ) {}
}
