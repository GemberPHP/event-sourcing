<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Saga;

use Exception;
use Throwable;

final class SagaCommandDispatchFailedException extends Exception
{
    public static function create(string $sagaClassName, Throwable $previous): self
    {
        return new self(sprintf('Dispatching commands recorded by saga %s failed', $sagaClassName), 0, $previous);
    }
}
