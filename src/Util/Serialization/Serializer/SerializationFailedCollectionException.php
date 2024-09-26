<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\Serialization\Serializer;

final class SerializationFailedCollectionException extends SerializationFailedException
{
    /**
     * @var list<SerializationFailedException>
     */
    private array $exceptions = [];

    public static function withExceptions(string $message, SerializationFailedException ...$exceptions): self
    {
        $exception = new self(sprintf('Serialization failed: %s', $message));
        $exception->exceptions = array_values($exceptions);

        return $exception;
    }

    /**
     * @return list<SerializationFailedException>
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }
}
