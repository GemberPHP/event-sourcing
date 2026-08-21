<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Outbox;

interface OutboxStore
{
    /**
     * @throws OutboxStoreFailedException
     */
    public function append(OutboxMessageType $type, object $message): void;

    /**
     * @throws OutboxStoreFailedException
     *
     * @return list<OutboxMessage>
     */
    public function getUnprocessedMessages(int $limit): array;

    /**
     * @throws OutboxStoreFailedException
     */
    public function markAsProcessed(string ...$messageIds): void;

    /**
     * Increment retry count and dead-letter the message if maxRetries is reached.
     *
     * @throws OutboxStoreFailedException
     */
    public function markAsFailed(int $maxRetries, string ...$messageIds): void;
}
