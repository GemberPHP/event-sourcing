<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Snapshot;

interface SnapshotStore
{
    /**
     * A snapshot is identified by its domain tags and names: the subscribed event classes, plus an entry for the
     * use case class and snapshot version. Store and load it by all of them, regardless of order.
     *
     * @param list<string> $domainTags
     * @param list<string> $eventNames
     */
    public function load(array $domainTags, array $eventNames): ?SnapshotEnvelope;

    public function save(SnapshotEnvelope $snapshot): void;
}
