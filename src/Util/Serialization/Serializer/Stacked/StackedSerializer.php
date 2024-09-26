<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\Serialization\Serializer\Stacked;

use Gember\EventSourcing\Util\Serialization\Serializer\SerializationFailedCollectionException;
use Gember\EventSourcing\Util\Serialization\Serializer\SerializationFailedException;
use Gember\EventSourcing\Util\Serialization\Serializer\Serializer;
use Override;

final readonly class StackedSerializer implements Serializer
{
    /**
     * @param iterable<Serializer> $serializers
     */
    public function __construct(
        private iterable $serializers,
    ) {}

    #[Override]
    public function serialize(object $object): string
    {
        $exceptions = [];
        foreach ($this->serializers as $serializer) {
            try {
                return $serializer->serialize($object);
            } catch (SerializationFailedException $exception) {
                $exceptions[] = $exception;

                continue;
            }
        }

        throw SerializationFailedCollectionException::withExceptions(
            'All serializers failed to serialize (see inner exceptions for more info)',
            ...$exceptions,
        );
    }

    #[Override]
    public function deserialize(string $payload, string $className): object
    {
        $exceptions = [];
        foreach ($this->serializers as $serializer) {
            try {
                return $serializer->deserialize($payload, $className);
            } catch (SerializationFailedException $exception) {
                $exceptions[] = $exception;

                continue;
            }
        }

        throw SerializationFailedCollectionException::withExceptions(
            'All serializers failed to deserialize (see inner exceptions for more info)',
            ...$exceptions,
        );
    }
}
