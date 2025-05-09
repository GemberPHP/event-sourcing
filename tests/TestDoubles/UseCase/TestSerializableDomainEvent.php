<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\UseCase;

use Gember\EventSourcing\UseCase\SerializableDomainEvent;

/**
 * @implements SerializableDomainEvent<array{
 *     id: string
 * }>
 */
final readonly class TestSerializableDomainEvent implements SerializableDomainEvent
{
    public function __construct(
        public string $id,
    ) {}

    public static function fromPayload(array $payload): SerializableDomainEvent
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
