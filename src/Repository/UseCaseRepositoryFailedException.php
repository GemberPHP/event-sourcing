<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Repository;

use Exception;
use Throwable;

final class UseCaseRepositoryFailedException extends Exception
{
    public static function withException(Throwable $exception): self
    {
        return new self(
            sprintf('UseCaseRepository request failed: %s', $exception->getMessage()),
            previous: $exception,
        );
    }
}
