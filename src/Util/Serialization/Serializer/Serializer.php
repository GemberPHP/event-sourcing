<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\Serialization\Serializer;

interface Serializer
{
    /**
     * @throws SerializationFailedException
     */
    public function serialize(object $object): string;

    /**
     * @param class-string $className
     *
     * @throws SerializationFailedException
     */
    public function deserialize(string $payload, string $className): object;
}
