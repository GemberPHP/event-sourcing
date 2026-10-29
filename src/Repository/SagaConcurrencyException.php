<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Repository;

use Exception;
use Throwable;

final class SagaConcurrencyException extends Exception
{
    public static function create(?Throwable $previous = null): self
    {
        return new self('Saga was changed concurrently', 0, $previous);
    }
}
