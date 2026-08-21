<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Outbox;

use Exception;
use Throwable;

final class OutboxStoreFailedException extends Exception
{
    public static function withException(Throwable $exception): self
    {
        return new self(
            sprintf('OutboxStore request failed: %s', $exception->getMessage()),
            previous: $exception,
        );
    }
}
