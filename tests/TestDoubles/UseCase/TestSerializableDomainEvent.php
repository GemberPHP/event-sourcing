<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\UseCase;

use Gember\EventSourcing\Util\Serialization\Serializable;

/**
 * @implements Serializable<array{
 *     id: string
 * }, TestSerializableDomainEvent>
 */
final readonly class TestSerializableDomainEvent implements Serializable
{
    public function __construct(
        public string $id,
    ) {}

    public static function fromPayload(array $payload): self
    {
        return new self($payload['id']);
    }

    public function toPayload(): array
    {
        return [
            'id' => $this->id,
        ];
    }
}
