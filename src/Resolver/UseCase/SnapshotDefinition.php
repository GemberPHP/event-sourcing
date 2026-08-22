<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase;

use Gember\EventSourcing\Util\Serialization\Serializable;

/**
 * @phpstan-type SnapshotDefinitionPayload array{
 *     afterEvents: ?int,
 *     afterSourcingTimeMs: ?int,
 *     onEvents: ?list<class-string>
 * }
 *
 * @implements Serializable<SnapshotDefinitionPayload, SnapshotDefinition>
 */
final readonly class SnapshotDefinition implements Serializable
{
    /**
     * @param list<class-string>|null $onEvents
     */
    public function __construct(
        public ?int $afterEvents = null,
        public ?int $afterSourcingTimeMs = null,
        public ?array $onEvents = null,
    ) {}

    public function toPayload(): array
    {
        return [
            'afterEvents' => $this->afterEvents,
            'afterSourcingTimeMs' => $this->afterSourcingTimeMs,
            'onEvents' => $this->onEvents,
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            $payload['afterEvents'],
            $payload['afterSourcingTimeMs'],
            $payload['onEvents'],
        );
    }
}
