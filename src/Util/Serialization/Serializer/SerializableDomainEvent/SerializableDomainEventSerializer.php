<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\Serialization\Serializer\SerializableDomainEvent;

use Gember\DependencyContracts\Util\Serialization\Serializer\SerializationFailedException;
use Gember\DependencyContracts\Util\Serialization\Serializer\Serializer;
use Gember\EventSourcing\UseCase\SerializableDomainEvent;
use JsonException;
use Override;

final readonly class SerializableDomainEventSerializer implements Serializer
{
    #[Override]
    public function serialize(object $object): string
    {
        if (!$object instanceof SerializableDomainEvent) {
            throw SerializationFailedException::withMessage(
                sprintf('Missing SerializableDomainEvent interface for %s', $object::class),
            );
        }

        try {
            return json_encode($object->toPayload(), JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw SerializationFailedException::withException($exception);
        }
    }

    #[Override]
    public function deserialize(string $payload, string $className): object
    {
        if (!is_subclass_of($className, SerializableDomainEvent::class)) {
            throw SerializationFailedException::withMessage(
                sprintf('Missing SerializableDomainEvent interface for %s', $className),
            );
        }

        try {
            /** @var array<string, mixed> $payload */
            $payload = (array) json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

            return $className::fromPayload($payload);
        } catch (JsonException $exception) {
            throw SerializationFailedException::withException($exception);
        }
    }
}
