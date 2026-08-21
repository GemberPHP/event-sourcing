<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Outbox\Rdbms;

use DateTimeImmutable;
use Exception;
use Gember\DependencyContracts\Outbox\Rdbms\RdbmsOutboxMessage;
use Gember\EventSourcing\Outbox\OutboxMessageType;
use Gember\EventSourcing\Outbox\OutboxStoreFailedException;
use Gember\EventSourcing\Outbox\Rdbms\RdbmsOutboxStore;
use Gember\EventSourcing\Test\TestDoubles\Outbox\TestRdbmsOutboxRepository;
use Gember\EventSourcing\Test\TestDoubles\Util\Generator\Identity\TestIdentityGenerator;
use Gember\EventSourcing\Test\TestDoubles\Util\Serialization\Serializer\TestSerializer;
use Gember\EventSourcing\Test\TestDoubles\Util\Time\Clock\TestClock;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @internal
 */
final class RdbmsOutboxStoreTest extends TestCase
{
    private TestRdbmsOutboxRepository $repository;
    private TestSerializer $serializer;
    private TestClock $clock;
    private RdbmsOutboxStore $store;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new TestRdbmsOutboxRepository();
        $this->serializer = new TestSerializer();
        $this->clock = new TestClock();
        $this->clock->time = new DateTimeImmutable('2024-10-14 12:00:00');
        $this->store = new RdbmsOutboxStore(
            $this->repository,
            $this->serializer,
            new TestIdentityGenerator(),
            $this->clock,
        );
    }

    #[Test]
    public function itShouldAppendMessage(): void
    {
        $event = new stdClass();

        $this->store->append(OutboxMessageType::Event, $event);

        self::assertCount(1, $this->repository->savedMessages);
        self::assertSame('be07b19b-c7ab-429e-a9c3-6b7d942122c0', $this->repository->savedMessages[0]->id);
        self::assertSame('event', $this->repository->savedMessages[0]->messageType);
        self::assertSame(stdClass::class, $this->repository->savedMessages[0]->messageName);
        self::assertSame(serialize($event), $this->repository->savedMessages[0]->payload);
    }

    #[Test]
    public function itShouldThrowExceptionWhenAppendFails(): void
    {
        $this->repository->shouldThrow = new Exception('save failed');

        $this->expectException(OutboxStoreFailedException::class);

        $this->store->append(OutboxMessageType::Event, new stdClass());
    }

    #[Test]
    public function itShouldGetUnprocessedMessages(): void
    {
        $event = new stdClass();

        $this->repository->unprocessedMessages = [
            new RdbmsOutboxMessage(
                'message-id-1',
                'event',
                stdClass::class,
                serialize($event),
                new DateTimeImmutable('2024-10-14 12:00:00'),
            ),
        ];

        $messages = $this->store->getUnprocessedMessages(10);

        self::assertCount(1, $messages);
        self::assertSame('message-id-1', $messages[0]->id);
        self::assertSame(OutboxMessageType::Event, $messages[0]->type);
        self::assertInstanceOf(stdClass::class, $messages[0]->message);
    }

    #[Test]
    public function itShouldThrowExceptionWhenGetUnprocessedMessagesFails(): void
    {
        $this->repository->shouldThrow = new Exception('get failed');

        $this->expectException(OutboxStoreFailedException::class);

        $this->store->getUnprocessedMessages(10);
    }

    #[Test]
    public function itShouldMarkAsProcessed(): void
    {
        $this->store->markAsProcessed('id-1', 'id-2');

        self::assertSame(['id-1', 'id-2'], $this->repository->processedMessageIds);
    }

    #[Test]
    public function itShouldThrowExceptionWhenMarkAsProcessedFails(): void
    {
        $this->repository->shouldThrow = new Exception('mark failed');

        $this->expectException(OutboxStoreFailedException::class);

        $this->store->markAsProcessed('id-1');
    }

    #[Test]
    public function itShouldMarkAsFailed(): void
    {
        $this->store->markAsFailed(5, 'id-1', 'id-2');

        self::assertSame(['id-1', 'id-2'], $this->repository->incrementedRetryCountIds);
    }

    #[Test]
    public function itShouldThrowExceptionWhenMarkAsFailedFails(): void
    {
        $this->repository->shouldThrow = new Exception('increment failed');

        $this->expectException(OutboxStoreFailedException::class);

        $this->store->markAsFailed(5, 'id-1');
    }
}
