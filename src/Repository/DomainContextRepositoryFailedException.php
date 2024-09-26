<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Repository;

use Exception;
use Throwable;

final class DomainContextRepositoryFailedException extends Exception
{
    public static function withException(Throwable $exception): self
    {
        return new self(
            sprintf('DomainContextRepository request failed: %s', $exception->getMessage()),
            previous: $exception,
        );
    }
}
