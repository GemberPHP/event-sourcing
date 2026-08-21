<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Outbox\Bus;

use Gember\EventSourcing\Outbox\Bus\OutboxEventBus;
use Gember\EventSourcing\Outbox\OutboxMessageType;
use Gember\EventSourcing\Test\TestDoubles\Outbox\TestOutboxStore;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @internal
 */
final class OutboxEventBusTest extends TestCase
{
    private TestOutboxStore $outboxStore;
    private OutboxEventBus $bus;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->outboxStore = new TestOutboxStore();
        $this->bus = new OutboxEventBus($this->outboxStore);
    }

    #[Test]
    public function itShouldAppendEventToOutbox(): void
    {
        $event = new stdClass();

        $this->bus->handle($event);

        self::assertCount(1, $this->outboxStore->appendedMessages);
        self::assertSame(OutboxMessageType::Event, $this->outboxStore->appendedMessages[0]['type']);
        self::assertSame($event, $this->outboxStore->appendedMessages[0]['message']);
    }
}
