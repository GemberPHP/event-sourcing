<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Outbox;

use Exception;
use Gember\DependencyContracts\Outbox\Rdbms\RdbmsOutboxMessage;
use Gember\DependencyContracts\Outbox\Rdbms\RdbmsOutboxRepository;

/**
 * @internal
 */
final class TestRdbmsOutboxRepository implements RdbmsOutboxRepository
{
    /**
     * @var list<RdbmsOutboxMessage>
     */
    public array $savedMessages = [];

    /**
     * @var list<RdbmsOutboxMessage>
     */
    public array $unprocessedMessages = [];

    /**
     * @var list<string>
     */
    public array $processedMessageIds = [];

    public ?Exception $shouldThrow = null;

    /**
     * @var list<string>
     */
    public array $incrementedRetryCountIds = [];

    public function getUnprocessedMessages(int $limit): array
    {
        if ($this->shouldThrow !== null) {
            throw $this->shouldThrow;
        }

        return array_slice($this->unprocessedMessages, 0, $limit);
    }

    public function save(RdbmsOutboxMessage $message): void
    {
        if ($this->shouldThrow !== null) {
            throw $this->shouldThrow;
        }

        $this->savedMessages[] = $message;
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

    public function incrementRetryCount(int $maxRetries, string ...$messageIds): void
    {
        if ($this->shouldThrow !== null) {
            throw $this->shouldThrow;
        }

        foreach ($messageIds as $id) {
            $this->incrementedRetryCountIds[] = $id;
        }
    }
}
