<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Snapshot\Policy;

final readonly class SnapshotContext
{
    /**
     * @param list<object> $appliedEvents
     */
    public function __construct(
        public float $sourcingDurationMs,
        public int $eventCount,
        public int $eventCountAtLastSnapshot,
        public array $appliedEvents,
    ) {}
}
