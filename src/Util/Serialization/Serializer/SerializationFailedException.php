<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\Serialization\Serializer;

use Exception;
use Throwable;

class SerializationFailedException extends Exception
{
    public static function withMessage(string $message): self
    {
        return new self(sprintf('Serialization failed: %s', $message));
    }

    public static function withException(Throwable $exception): self
    {
        return new self(
            sprintf('Serialization failed: %s', $exception->getMessage()),
            previous: $exception,
        );
    }
}
