<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\Cache;

use Exception;
use Throwable;

final class CacheException extends Exception
{
    public static function withException(Throwable $exception): self
    {
        return new self(
            sprintf('Cache failed: %s', $exception->getMessage()),
            previous: $exception,
        );
    }
}
