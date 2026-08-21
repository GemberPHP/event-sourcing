<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Outbox\Processor\Default;

use Exception;
use Gember\DependencyContracts\Util\Messaging\MessageBus\EventBus;
use Gember\EventSourcing\Outbox\OutboxMessage;
use Gember\EventSourcing\Outbox\OutboxMessageType;
use Gember\EventSourcing\Outbox\Processor\Default\DefaultOutboxProcessor;
use Gember\EventSourcing\Test\TestDoubles\Outbox\TestOutboxStore;
use Gember\EventSourcing\Test\TestDoubles\Util\Log\TestLogger;
use Gember\EventSourcing\Test\TestDoubles\Util\Messaging\MessageBus\TestCommandBus;
use Gember\EventSourcing\Test\TestDoubles\Util\Messaging\MessageBus\TestEventBus;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @internal
 */
final class DefaultOutboxProcessorTest extends TestCase
{
    private TestOutboxStore $outboxStore;
    private TestEventBus $eventBus;
    private TestCommandBus $commandBus;
    private TestLogger $logger;
    private DefaultOutboxProcessor $processor;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->outboxStore = new TestOutboxStore();
        $this->eventBus = new TestEventBus();
        $this->commandBus = new TestCommandBus();
        $this->logger = new TestLogger();
        $this->processor = new DefaultOutboxProcessor(
            $this->outboxStore,
            $this->eventBus,
            $this->commandBus,
            $this->logger,
        );
    }

    #[Test]
    public function itShouldProcessEventMessages(): void
    {
        $event = new stdClass();

        $this->outboxStore->unprocessedMessages = [
            new OutboxMessage('msg-1', OutboxMessageType::Event, $event),
        ];

        $result = $this->processor->process();

        self::assertSame(1, $result);
        self::assertCount(1, $this->eventBus->events);
        self::assertSame($event, $this->eventBus->events[0]);
        self::assertSame(['msg-1'], $this->outboxStore->processedMessageIds);
    }

    #[Test]
    public function itShouldProcessCommandMessages(): void
    {
        $command = new stdClass();

        $this->outboxStore->unprocessedMessages = [
            new OutboxMessage('msg-1', OutboxMessageType::Command, $command),
        ];

        $result = $this->processor->process();

        self::assertSame(1, $result);
        self::assertCount(1, $this->commandBus->commands);
        self::assertSame($command, $this->commandBus->commands[0]);
        self::assertSame(['msg-1'], $this->outboxStore->processedMessageIds);
    }

    #[Test]
    public function itShouldProcessMixedMessages(): void
    {
        $event = new stdClass();
        $command = new stdClass();

        $this->outboxStore->unprocessedMessages = [
            new OutboxMessage('msg-1', OutboxMessageType::Event, $event),
            new OutboxMessage('msg-2', OutboxMessageType::Command, $command),
        ];

        $result = $this->processor->process();

        self::assertSame(2, $result);
        self::assertCount(1, $this->eventBus->events);
        self::assertCount(1, $this->commandBus->commands);
        self::assertSame(['msg-1', 'msg-2'], $this->outboxStore->processedMessageIds);
    }

    #[Test]
    public function itShouldMarkFailedMessagesAndLogAndContinue(): void
    {
        $event1 = new stdClass();
        $event2 = new stdClass();

        $throwingEventBus = new class implements EventBus {
            public int $callCount = 0;

            public function handle(object $event): void
            {
                ++$this->callCount;
                if ($this->callCount === 1) {
                    throw new Exception('dispatch failed');
                }
            }
        };

        $processor = new DefaultOutboxProcessor(
            $this->outboxStore,
            $throwingEventBus,
            $this->commandBus,
            $this->logger,
        );

        $this->outboxStore->unprocessedMessages = [
            new OutboxMessage('msg-1', OutboxMessageType::Event, $event1),
            new OutboxMessage('msg-2', OutboxMessageType::Event, $event2),
        ];

        $result = $processor->process();

        self::assertSame(1, $result);

        // msg-1 failed: marked as failed and logged
        self::assertSame(['msg-1'], $this->outboxStore->failedMessageIds);
        self::assertCount(1, $this->logger->logs);
        self::assertSame('[Outbox] Failed dispatching event stdClass', $this->logger->logs[0]['message']);
        self::assertSame('msg-1', $this->logger->logs[0]['context']['messageId']);
        self::assertSame('dispatch failed', $this->logger->logs[0]['context']['exception']);

        // msg-2 succeeded
        self::assertSame(['msg-2'], $this->outboxStore->processedMessageIds);
    }

    #[Test]
    public function itShouldDoNothingWhenNoMessages(): void
    {
        $result = $this->processor->process();

        self::assertSame(0, $result);
        self::assertEmpty($this->eventBus->events);
        self::assertEmpty($this->commandBus->commands);
        self::assertEmpty($this->outboxStore->processedMessageIds);
    }
}
