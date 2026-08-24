<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Snapshot\Rdbms;

use DateTimeImmutable;
use Gember\DependencyContracts\EventStore\Snapshot\RdbmsSnapshot;
use Gember\DependencyContracts\EventStore\Snapshot\RdbmsSnapshotStoreRepository;
use Gember\DependencyContracts\Util\Generator\Identity\IdentityGenerator;
use Gember\EventSourcing\Snapshot\Rdbms\RdbmsSnapshotStore;
use Gember\EventSourcing\Snapshot\SnapshotEnvelope;
use Gember\EventSourcing\Util\Time\Clock\Clock;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TestIdentityGenerator implements IdentityGenerator
{
    public string $nextId = 'generated-id';

    #[Override]
    public function generate(): string
    {
        return $this->nextId;
    }
}

final class TestClock implements Clock
{
    public DateTimeImmutable $now;

    public function __construct()
    {
        $this->now = new DateTimeImmutable('2025-01-15 12:00:00.000000');
    }

    #[Override]
    public function now(string $time = 'now'): DateTimeImmutable
    {
        return $this->now;
    }
}

final class TestRdbmsSnapshotStoreRepository implements RdbmsSnapshotStoreRepository
{
    public ?RdbmsSnapshot $savedSnapshot = null;
    public ?RdbmsSnapshot $snapshotToReturn = null;

    #[Override]
    public function get(array $domainTags, array $eventNames): ?RdbmsSnapshot
    {
        return $this->snapshotToReturn;
    }

    #[Override]
    public function save(RdbmsSnapshot $snapshot): void
    {
        $this->savedSnapshot = $snapshot;
    }
}

/**
 * @internal
 */
final class RdbmsSnapshotStoreTest extends TestCase
{
    private TestRdbmsSnapshotStoreRepository $repository;
    private TestIdentityGenerator $identityGenerator;
    private TestClock $clock;
    private RdbmsSnapshotStore $store;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new TestRdbmsSnapshotStoreRepository();
        $this->identityGenerator = new TestIdentityGenerator();
        $this->clock = new TestClock();
        $this->store = new RdbmsSnapshotStore($this->repository, $this->identityGenerator, $this->clock);
    }

    #[Test]
    public function itShouldReturnNullWhenNoSnapshotExists(): void
    {
        $result = $this->store->load(['domain-tag'], ['event_name']);

        self::assertNull($result);
    }

    #[Test]
    public function itShouldLoadSnapshotFromRepository(): void
    {
        $this->repository->snapshotToReturn = new RdbmsSnapshot(
            'snapshot-id-1',
            ['domain-tag-1'],
            ['event_name_1'],
            'last-event-id',
            10,
            '{"state":"test"}',
            new DateTimeImmutable('2025-01-15 10:00:00.000000'),
        );

        $result = $this->store->load(['domain-tag-1'], ['event_name_1']);

        self::assertNotNull($result);
        self::assertSame(['domain-tag-1'], $result->domainTags);
        self::assertSame(['event_name_1'], $result->eventNames);
        self::assertSame('last-event-id', $result->lastEventId);
        self::assertSame(10, $result->eventCount);
        self::assertSame('{"state":"test"}', $result->state);
    }

    #[Test]
    public function itShouldSaveSnapshotWithGeneratedId(): void
    {
        $this->identityGenerator->nextId = 'generated-uuid';

        $envelope = new SnapshotEnvelope(
            ['domain-tag-1'],
            ['event_name_1'],
            'last-event-id',
            15,
            '{"state":"serialized"}',
        );

        $this->store->save($envelope);

        $savedSnapshot = $this->repository->savedSnapshot;

        self::assertNotNull($savedSnapshot);
        self::assertSame('generated-uuid', $savedSnapshot->id);
        self::assertSame(['domain-tag-1'], $savedSnapshot->domainTags);
        self::assertSame(['event_name_1'], $savedSnapshot->eventNames);
        self::assertSame('last-event-id', $savedSnapshot->lastEventId);
        self::assertSame(15, $savedSnapshot->eventCount);
        self::assertSame('{"state":"serialized"}', $savedSnapshot->payload);
        self::assertEquals(new DateTimeImmutable('2025-01-15 12:00:00.000000'), $savedSnapshot->createdAt);
    }
}
