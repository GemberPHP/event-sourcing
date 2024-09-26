<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\Messaging\MessageBus;

use Exception;
use Throwable;

final class HandlingMessageFailedException extends Exception
{
    public static function withException(Throwable $exception): self
    {
        return new self(
            sprintf('Handling message failed: %s', $exception->getMessage()),
            previous: $exception,
        );
    }
}
