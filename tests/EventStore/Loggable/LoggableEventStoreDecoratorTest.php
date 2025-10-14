<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\EventStore\Loggable;

use DateTimeImmutable;
use Exception;
use Gember\EventSourcing\EventStore\Loggable\LoggableEventStoreDecorator;
use Gember\EventSourcing\EventStore\StreamQuery;
use Gember\EventSourcing\Test\TestDoubles\EventStore\TestEventStore;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use Gember\EventSourcing\Test\TestDoubles\Util\Log\TestLogger;
use Gember\EventSourcing\UseCase\DomainEventEnvelope;
use Gember\EventSourcing\UseCase\Metadata;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Stringable;

/**
 * @internal
 */
final class LoggableEventStoreDecoratorTest extends TestCase
{
    private TestEventStore $innerEventStore;
    private TestLogger $logger;
    private LoggableEventStoreDecorator $decorator;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->innerEventStore = new TestEventStore();
        $this->logger = new TestLogger();
        $this->decorator = new LoggableEventStoreDecorator(
            $this->innerEventStore,
            $this->logger,
        );
    }

    #[Test]
    public function itShouldLogSuccessfulLoad(): void
    {
        $streamQuery = new StreamQuery(['domain-tag-1', 'domain-tag-2']);
        $appliedAt = new DateTimeImmutable('2024-10-14 12:00:00');

        $envelope1 = new DomainEventEnvelope(
            'event-id-1',
            ['domain-tag-1'],
            new TestUseCaseCreatedEvent('test-1', 'secondary-1'),
            new Metadata(['key' => 'value']),
            $appliedAt,
        );

        $envelope2 = new DomainEventEnvelope(
            'event-id-2',
            ['domain-tag-2'],
            new TestUseCaseModifiedEvent(),
            new Metadata(['another' => 'metadata']),
            $appliedAt,
        );

        $this->innerEventStore->envelopesToReturn = [$envelope1, $envelope2];

        $result = $this->decorator->load($streamQuery);

        self::assertTrue($this->innerEventStore->loadWasCalled);
        self::assertSame($streamQuery, $this->innerEventStore->lastLoadStreamQuery);
        self::assertSame([$envelope1, $envelope2], $result);

        self::assertCount(4, $this->logger->logs);

        self::assertSame('[EventStore] Started loading events', $this->logger->logs[0]['message']);
        self::assertSame(['domainTags' => ['domain-tag-1', 'domain-tag-2']], $this->logger->logs[0]['context']);

        self::assertSame('[EventStore] Loaded event TestUseCaseCreatedEvent', $this->logger->logs[1]['message']);
        self::assertSame('event-id-1', $this->logger->logs[1]['context']['eventId']);
        self::assertSame(['domain-tag-1'], $this->logger->logs[1]['context']['domainTags']);
        self::assertSame(['key' => 'value'], $this->logger->logs[1]['context']['metadata']);

        self::assertSame('[EventStore] Loaded event TestUseCaseModifiedEvent', $this->logger->logs[2]['message']);
        self::assertSame('event-id-2', $this->logger->logs[2]['context']['eventId']);
        self::assertSame(['domain-tag-2'], $this->logger->logs[2]['context']['domainTags']);
        self::assertSame(['another' => 'metadata'], $this->logger->logs[2]['context']['metadata']);

        self::assertSame('[EventStore] Ended loading events', $this->logger->logs[3]['message']);
        self::assertSame(['domain-tag-1', 'domain-tag-2'], $this->logger->logs[3]['context']['domainTags']);
    }

    #[Test]
    public function itShouldLogFailedLoad(): void
    {
        $streamQuery = new StreamQuery(['domain-tag-1']);
        $exception = new Exception('Load failed');
        $this->innerEventStore->loadShouldThrow = $exception;

        try {
            $this->decorator->load($streamQuery);
        } catch (Exception) {
            self::assertTrue($this->innerEventStore->loadWasCalled);

            self::assertCount(2, $this->logger->logs);

            self::assertSame('[EventStore] Started loading events', $this->logger->logs[0]['message']);
            self::assertSame(['domainTags' => ['domain-tag-1']], $this->logger->logs[0]['context']);

            self::assertSame('[EventStore] Failed loading events', $this->logger->logs[1]['message']);
            self::assertSame('Load failed', $this->logger->logs[1]['context']['exception']);
            self::assertSame(Exception::class, $this->logger->logs[1]['context']['exceptionClass']);
            self::assertSame(['domain-tag-1'], $this->logger->logs[1]['context']['domainTags']);
        }
    }

    #[Test]
    public function itShouldLogSuccessfulAppend(): void
    {
        $streamQuery = new StreamQuery(['domain-tag-1']);
        $lastEventId = 'last-event-id';
        $appliedAt = new DateTimeImmutable('2024-10-14 12:00:00');

        $envelope1 = new DomainEventEnvelope(
            'event-id-1',
            ['domain-tag-1'],
            new TestUseCaseCreatedEvent('test-1', 'secondary-1'),
            new Metadata(['key' => 'value']),
            $appliedAt,
        );

        $envelope2 = new DomainEventEnvelope(
            'event-id-2',
            ['domain-tag-1'],
            new TestUseCaseModifiedEvent(),
            new Metadata(),
            $appliedAt,
        );

        $this->decorator->append($streamQuery, $lastEventId, $envelope1, $envelope2);

        self::assertTrue($this->innerEventStore->appendWasCalled);
        self::assertSame($streamQuery, $this->innerEventStore->lastAppendStreamQuery);
        self::assertSame($lastEventId, $this->innerEventStore->lastAppendLastEventId);
        self::assertSame([$envelope1, $envelope2], $this->innerEventStore->lastAppendEventEnvelopes);

        self::assertCount(4, $this->logger->logs);

        self::assertSame('[EventStore] Started appending events', $this->logger->logs[0]['message']);
        self::assertSame(['domainTags' => ['domain-tag-1']], $this->logger->logs[0]['context']);

        self::assertSame('[EventStore] Appended event TestUseCaseCreatedEvent', $this->logger->logs[1]['message']);
        self::assertSame('event-id-1', $this->logger->logs[1]['context']['eventId']);
        self::assertSame(['domain-tag-1'], $this->logger->logs[1]['context']['domainTags']);
        self::assertSame(['key' => 'value'], $this->logger->logs[1]['context']['metadata']);

        self::assertSame('[EventStore] Appended event TestUseCaseModifiedEvent', $this->logger->logs[2]['message']);
        self::assertSame('event-id-2', $this->logger->logs[2]['context']['eventId']);
        self::assertSame(['domain-tag-1'], $this->logger->logs[2]['context']['domainTags']);
        self::assertSame([], $this->logger->logs[2]['context']['metadata']);

        self::assertSame('[EventStore] Ended appending events', $this->logger->logs[3]['message']);
        self::assertSame(['domain-tag-1'], $this->logger->logs[3]['context']['domainTags']);
    }

    #[Test]
    public function itShouldLogFailedAppend(): void
    {
        $streamQuery = new StreamQuery(['domain-tag-1']);
        $lastEventId = 'last-event-id';
        $appliedAt = new DateTimeImmutable('2024-10-14 12:00:00');

        $envelope = new DomainEventEnvelope(
            'event-id-1',
            ['domain-tag-1'],
            new TestUseCaseCreatedEvent('test-1', 'secondary-1'),
            new Metadata(),
            $appliedAt,
        );

        $exception = new Exception('Append failed');
        $this->innerEventStore->appendShouldThrow = $exception;

        try {
            $this->decorator->append($streamQuery, $lastEventId, $envelope);
        } catch (Exception) {
            self::assertTrue($this->innerEventStore->appendWasCalled);

            self::assertCount(2, $this->logger->logs);

            self::assertSame('[EventStore] Started appending events', $this->logger->logs[0]['message']);
            self::assertSame(['domainTags' => ['domain-tag-1']], $this->logger->logs[0]['context']);

            self::assertSame('[EventStore] Failed appending events', $this->logger->logs[1]['message']);
            self::assertSame('Append failed', $this->logger->logs[1]['context']['exception']);
            self::assertSame(['domain-tag-1'], $this->logger->logs[1]['context']['domainTags']);
        }
    }

    #[Test]
    public function itShouldHandleStringableDomainTags(): void
    {
        $domainTag = new class implements Stringable {
            #[Override]
            public function __toString(): string
            {
                return 'stringable-domain-tag';
            }
        };

        $streamQuery = new StreamQuery([$domainTag]);
        $this->innerEventStore->envelopesToReturn = [];

        $this->decorator->load($streamQuery);

        self::assertCount(2, $this->logger->logs);
        self::assertSame(['domainTags' => ['stringable-domain-tag']], $this->logger->logs[0]['context']);
        self::assertSame(['stringable-domain-tag'], $this->logger->logs[1]['context']['domainTags']);
    }

    #[Test]
    public function itShouldHandleLoadWithNoEvents(): void
    {
        $streamQuery = new StreamQuery(['domain-tag-1']);
        $this->innerEventStore->envelopesToReturn = [];

        $result = $this->decorator->load($streamQuery);

        self::assertSame([], $result);
        self::assertCount(2, $this->logger->logs);

        self::assertSame('[EventStore] Started loading events', $this->logger->logs[0]['message']);

        self::assertSame('[EventStore] Ended loading events', $this->logger->logs[1]['message']);
    }
}
