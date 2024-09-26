<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Util\Serialization\Serializer;

use Gember\EventSourcing\Util\Serialization\Serializer\SerializationFailedException;
use Gember\EventSourcing\Util\Serialization\Serializer\Serializer;

final readonly class TestThrowingExceptionSerializer implements Serializer
{
    public function __construct(
        private SerializationFailedException $exception = new SerializationFailedException('It failed'),
    ) {}

    public function serialize(object $object): string
    {
        throw $this->exception;
    }

    public function deserialize(string $payload, string $className): object
    {
        throw $this->exception;
    }
}
