<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Snapshot\Loggable;

use Exception;
use Gember\EventSourcing\Snapshot\Loggable\LoggableSnapshotStoreDecorator;
use Gember\EventSourcing\Snapshot\SnapshotEnvelope;
use Gember\EventSourcing\Test\TestDoubles\Snapshot\TestSnapshotStore;
use Gember\EventSourcing\Test\TestDoubles\Util\Log\TestLogger;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class LoggableSnapshotStoreDecoratorTest extends TestCase
{
    private TestSnapshotStore $innerStore;
    private TestLogger $logger;
    private LoggableSnapshotStoreDecorator $decorator;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->innerStore = new TestSnapshotStore();
        $this->logger = new TestLogger();
        $this->decorator = new LoggableSnapshotStoreDecorator(
            $this->innerStore,
            $this->logger,
        );
    }

    #[Test]
    public function itShouldLogWhenSnapshotFound(): void
    {
        $this->innerStore->snapshotToReturn = new SnapshotEnvelope(
            ['tag-1'],
            ['EventA'],
            'event-99',
            99,
            'serialized-state',
        );

        $result = $this->decorator->load(['tag-1'], ['EventA']);

        self::assertNotNull($result);
        self::assertCount(2, $this->logger->logs);
        self::assertSame('[Snapshot] Started loading snapshot', $this->logger->logs[0]['message']);
        self::assertSame('[Snapshot] Loaded snapshot', $this->logger->logs[1]['message']);
        self::assertSame('event-99', $this->logger->logs[1]['context']['lastEventId']);
        self::assertSame(99, $this->logger->logs[1]['context']['eventCount']);
    }

    #[Test]
    public function itShouldLogWhenNoSnapshotFound(): void
    {
        $result = $this->decorator->load(['tag-1'], ['EventA']);

        self::assertNull($result);
        self::assertCount(2, $this->logger->logs);
        self::assertSame('[Snapshot] Started loading snapshot', $this->logger->logs[0]['message']);
        self::assertSame('[Snapshot] No snapshot found', $this->logger->logs[1]['message']);
        self::assertSame(['tag-1'], $this->logger->logs[1]['context']['domainTags']);
    }

    #[Test]
    public function itShouldLogLoadFailure(): void
    {
        $this->innerStore->loadShouldThrow = new Exception('load failed');

        self::expectException(Exception::class);
        self::expectExceptionMessage('load failed');

        $this->decorator->load(['tag-1'], ['EventA']);
    }

    #[Test]
    public function itShouldLogSuccessfulSave(): void
    {
        $snapshot = new SnapshotEnvelope(
            ['tag-1'],
            ['EventA'],
            'event-100',
            100,
            'serialized-state',
        );

        $this->decorator->save($snapshot);

        self::assertTrue($this->innerStore->saveWasCalled);
        self::assertCount(2, $this->logger->logs);
        self::assertSame('[Snapshot] Started saving snapshot', $this->logger->logs[0]['message']);
        self::assertSame('event-100', $this->logger->logs[0]['context']['lastEventId']);
        self::assertSame('[Snapshot] Finished saving snapshot', $this->logger->logs[1]['message']);
        self::assertArrayHasKey('duration', $this->logger->logs[1]['context']);
    }

    #[Test]
    public function itShouldLogSaveFailure(): void
    {
        $this->innerStore->saveShouldThrow = new Exception('save failed');

        $snapshot = new SnapshotEnvelope(
            ['tag-1'],
            ['EventA'],
            'event-100',
            100,
            'serialized-state',
        );

        self::expectException(Exception::class);
        self::expectExceptionMessage('save failed');

        $this->decorator->save($snapshot);
    }
}
