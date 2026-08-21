<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Outbox\Processor;

interface OutboxProcessor
{
    /**
     * @return int Number of messages processed
     */
    public function process(int $limit = 100): int;
}
