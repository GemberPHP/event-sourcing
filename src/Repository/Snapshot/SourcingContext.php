<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Repository\Snapshot;

use Gember\EventSourcing\Resolver\UseCase\SnapshotDefinition;

final readonly class SourcingContext
{
    /**
     * @param list<string> $domainTags
     * @param list<string> $eventNames
     */
    public function __construct(
        public float $sourcingDurationMs,
        public int $eventCount,
        public int $eventCountAtLastSnapshot,
        public SnapshotDefinition $snapshotDefinition,
        public array $domainTags,
        public array $eventNames,
    ) {}
}
