<?php

declare(strict_types=1);

namespace Gember\EventSourcing\EventStore;

use Exception;
use Throwable;

final class EventStoreFailedException extends Exception
{
    public static function withException(Throwable $exception): self
    {
        return new self(
            sprintf('EventStore request failed: %s', $exception->getMessage()),
            previous: $exception,
        );
    }
}
