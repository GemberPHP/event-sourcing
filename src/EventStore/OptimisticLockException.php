<?php

declare(strict_types=1);

namespace Gember\EventSourcing\EventStore;

use Exception;

final class OptimisticLockException extends Exception
{
    public static function create(): self
    {
        return new self('Optimistic lock: event store is already changed');
    }
}
