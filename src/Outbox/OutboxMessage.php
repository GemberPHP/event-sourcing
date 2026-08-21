<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Outbox;

final readonly class OutboxMessage
{
    public function __construct(
        public string $id,
        public OutboxMessageType $type,
        public object $message,
    ) {}
}
