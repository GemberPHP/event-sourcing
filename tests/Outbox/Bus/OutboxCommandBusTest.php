<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Outbox\Bus;

use Gember\EventSourcing\Outbox\Bus\OutboxCommandBus;
use Gember\EventSourcing\Outbox\OutboxMessageType;
use Gember\EventSourcing\Test\TestDoubles\Outbox\TestOutboxStore;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @internal
 */
final class OutboxCommandBusTest extends TestCase
{
    private TestOutboxStore $outboxStore;
    private OutboxCommandBus $bus;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->outboxStore = new TestOutboxStore();
        $this->bus = new OutboxCommandBus($this->outboxStore);
    }

    #[Test]
    public function itShouldAppendCommandToOutbox(): void
    {
        $command = new stdClass();

        $this->bus->handle($command);

        self::assertCount(1, $this->outboxStore->appendedMessages);
        self::assertSame(OutboxMessageType::Command, $this->outboxStore->appendedMessages[0]['type']);
        self::assertSame($command, $this->outboxStore->appendedMessages[0]['message']);
    }
}
