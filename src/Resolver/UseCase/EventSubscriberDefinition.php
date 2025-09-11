<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase;

use Gember\EventSourcing\Util\Serialization\Serializable;

/**
 * @phpstan-type EventSubscriberDefinitionPayload array{
 *      eventClassName: class-string,
 *      methodName: string
 * }
 *
 * @implements Serializable<EventSubscriberDefinitionPayload, EventSubscriberDefinition>
 */
final readonly class EventSubscriberDefinition implements Serializable
{
    /**
     * @param class-string $eventClassName
     */
    public function __construct(
        public string $eventClassName,
        public string $methodName,
    ) {}

    public function toPayload(): array
    {
        return [
            'eventClassName' => $this->eventClassName,
            'methodName' => $this->methodName,
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            $payload['eventClassName'],
            $payload['methodName'],
        );
    }
}
