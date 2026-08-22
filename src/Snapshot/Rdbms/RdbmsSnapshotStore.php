<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Snapshot\Rdbms;

use Gember\DependencyContracts\EventStore\Snapshot\RdbmsSnapshot;
use Gember\DependencyContracts\EventStore\Snapshot\RdbmsSnapshotStoreRepository;
use Gember\EventSourcing\Snapshot\SnapshotEnvelope;
use Gember\EventSourcing\Snapshot\SnapshotStore;
use Override;
use DateTimeImmutable;

final readonly class RdbmsSnapshotStore implements SnapshotStore
{
    public function __construct(
        private RdbmsSnapshotStoreRepository $repository,
    ) {}

    #[Override]
    public function load(array $domainTags, array $eventNames): ?SnapshotEnvelope
    {
        $rdbmsSnapshot = $this->repository->get(
            $domainTags,
            $eventNames,
        );

        if ($rdbmsSnapshot === null) {
            return null;
        }

        return new SnapshotEnvelope(
            $rdbmsSnapshot->domainTags,
            $rdbmsSnapshot->eventNames,
            $rdbmsSnapshot->lastEventId,
            $rdbmsSnapshot->eventCount,
            $rdbmsSnapshot->payload,
        );
    }

    #[Override]
    public function save(SnapshotEnvelope $snapshot): void
    {
        $this->repository->save(new RdbmsSnapshot(
            $snapshot->domainTags,
            $snapshot->eventNames,
            $snapshot->lastEventId,
            $snapshot->eventCount,
            $snapshot->state,
            new DateTimeImmutable(),
        ));
    }
}
