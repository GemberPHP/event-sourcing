<?php

declare(strict_types=1);

namespace Gember\EventSourcing\UseCase;

/**
 * @template T of array<string, mixed>
 */
interface SerializableDomainEvent
{
    /**
     * @param T $payload
     *
     * @return SerializableDomainEvent<T>
     */
    public static function fromPayload(array $payload): self;

    /**
     * @return T
     */
    public function toPayload(): array;
}
