<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Snapshot;

interface SnapshotStore
{
    /**
     * @param list<string> $domainTags
     * @param list<string> $eventNames
     */
    public function load(array $domainTags, array $eventNames): ?SnapshotEnvelope;

    public function save(SnapshotEnvelope $snapshot): void;
}
