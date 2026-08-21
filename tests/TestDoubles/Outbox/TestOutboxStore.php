<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Outbox;

use Exception;
use Gember\EventSourcing\Outbox\OutboxMessage;
use Gember\EventSourcing\Outbox\OutboxMessageType;
use Gember\EventSourcing\Outbox\OutboxStore;

/**
 * @internal
 */
final class TestOutboxStore implements OutboxStore
{
    /**
     * @var list<array{type: OutboxMessageType, message: object}>
     */
    public array $appendedMessages = [];

    /**
     * @var list<OutboxMessage>
     */
    public array $unprocessedMessages = [];

    /**
     * @var list<string>
     */
    public array $processedMessageIds = [];

    public ?Exception $shouldThrow = null;

    public function append(OutboxMessageType $type, object $message): void
    {
        if ($this->shouldThrow !== null) {
            throw $this->shouldThrow;
        }

        $this->appendedMessages[] = ['type' => $type, 'message' => $message];
    }

    /**
     * @var list<string>
     */
    public array $failedMessageIds = [];

    public function getUnprocessedMessages(int $limit): array
    {
        if ($this->shouldThrow !== null) {
            throw $this->shouldThrow;
        }

        return array_slice($this->unprocessedMessages, 0, $limit);
    }

    public function markAsProcessed(string ...$messageIds): void
    {
        if ($this->shouldThrow !== null) {
            throw $this->shouldThrow;
        }

        foreach ($messageIds as $id) {
            $this->processedMessageIds[] = $id;
        }
    }

    public function markAsFailed(int $maxRetries, string ...$messageIds): void
    {
        if ($this->shouldThrow !== null) {
            throw $this->shouldThrow;
        }

        foreach ($messageIds as $id) {
            $this->failedMessageIds[] = $id;
        }
    }
}
