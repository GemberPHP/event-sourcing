<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\Serialization\Serializer\Interface;

use Gember\DependencyContracts\Util\Serialization\Serializer\SerializationFailedException;
use Gember\DependencyContracts\Util\Serialization\Serializer\Serializer;
use Gember\EventSourcing\Util\Serialization\Serializable;
use JsonException;
use Override;

final readonly class SerializableInterfaceSerializer implements Serializer
{
    #[Override]
    public function serialize(object $object): string
    {
        if (!$object instanceof Serializable) {
            throw SerializationFailedException::withMessage(
                sprintf('Missing Serializable interface for %s', $object::class),
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
        if (!is_subclass_of($className, Serializable::class)) {
            throw SerializationFailedException::withMessage(
                sprintf('Missing Serializable interface for %s', $className),
            );
        }

        try {
            $payload = (array) json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

            /** @var array<string, mixed> $payload */
            return $className::fromPayload($payload);
        } catch (JsonException $exception) {
            throw SerializationFailedException::withException($exception);
        }
    }
}
