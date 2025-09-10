<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\Serialization;

/**
 * @template T of array
 * @template S of self
 */
interface Serializable
{
    /**
     * @return T
     */
    public function toPayload(): array;

    /**
     * @param T $payload
     *
     * @return S
     */
    public static function fromPayload(array $payload): self;
}
