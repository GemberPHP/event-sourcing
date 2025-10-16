<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Saga;

use Gember\EventSourcing\Resolver\Common\SagaId\SagaIdDefinition;
use Gember\EventSourcing\Util\Serialization\Serializable;

/**
 * @phpstan-import-type SagaIdDefinitionPayload from SagaIdDefinition
 * @phpstan-import-type SagaEventSubscriberDefinitionPayload from SagaEventSubscriberDefinition
 *
 * @phpstan-type SagaDefinitionPayload array{
 *     sagaClassName: class-string,
 *     sagaName: string,
 *     sagaIds: list<SagaIdDefinitionPayload>,
 *     eventSubscribers: list<SagaEventSubscriberDefinitionPayload>
 * }
 *
 * @implements Serializable<SagaDefinitionPayload, SagaDefinition>
 */
final readonly class SagaDefinition implements Serializable
{
    /**
     * @param class-string $sagaClassName
     * @param list<SagaIdDefinition> $sagaIds
     * @param list<SagaEventSubscriberDefinition> $eventSubscribers
     */
    public function __construct(
        public string $sagaClassName,
        public string $sagaName,
        public array $sagaIds,
        public array $eventSubscribers,
    ) {}

    public function toPayload(): array
    {
        return [
            'sagaClassName' => $this->sagaClassName,
            'sagaName' => $this->sagaName,
            'sagaIds' => array_map(fn($sagaId) => $sagaId->toPayload(), $this->sagaIds),
            'eventSubscribers' => array_map(fn($eventSubscriber) => $eventSubscriber->toPayload(), $this->eventSubscribers),
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            $payload['sagaClassName'],
            $payload['sagaName'],
            array_map(fn($sagaId) => SagaIdDefinition::fromPayload($sagaId), $payload['sagaIds']),
            array_map(fn($eventSubscriber) => SagaEventSubscriberDefinition::fromPayload($eventSubscriber), $payload['eventSubscribers']),
        );
    }
}
