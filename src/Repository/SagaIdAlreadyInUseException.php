<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Repository;

use Exception;
use Throwable;

final class SagaIdAlreadyInUseException extends Exception
{
    public static function create(Throwable $previous): self
    {
        return new self($previous->getMessage(), 0, $previous);
    }
}
