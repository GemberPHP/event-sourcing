<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Snapshot;

use Gember\EventSourcing\Snapshot\SnapshotEnvelope;
use Gember\EventSourcing\Snapshot\SnapshotStore;
use Exception;

/**
 * @internal
 */
final class TestSnapshotStore implements SnapshotStore
{
    public ?SnapshotEnvelope $storedSnapshot = null;
    public ?SnapshotEnvelope $snapshotToReturn = null;
    public bool $loadWasCalled = false;
    public bool $saveWasCalled = false;
    public ?Exception $loadShouldThrow = null;
    public ?Exception $saveShouldThrow = null;

    public function load(array $domainTags, array $eventNames): ?SnapshotEnvelope
    {
        $this->loadWasCalled = true;

        if ($this->loadShouldThrow !== null) {
            throw $this->loadShouldThrow;
        }

        return $this->snapshotToReturn;
    }

    public function save(SnapshotEnvelope $snapshot): void
    {
        $this->saveWasCalled = true;
        $this->storedSnapshot = $snapshot;

        if ($this->saveShouldThrow !== null) {
            throw $this->saveShouldThrow;
        }
    }
}
