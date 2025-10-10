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
 *     sagaId: SagaIdDefinitionPayload,
 *     eventSubscribers: list<SagaEventSubscriberDefinitionPayload>
 * }
 *
 * @implements Serializable<SagaDefinitionPayload, SagaDefinition>
 */
final readonly class SagaDefinition implements Serializable
{
    /**
     * @param class-string $sagaClassName
     * @param list<SagaEventSubscriberDefinition> $eventSubscribers
     */
    public function __construct(
        public string $sagaClassName,
        public string $sagaName,
        public SagaIdDefinition $sagaId,
        public array $eventSubscribers,
    ) {}

    public function toPayload(): array
    {
        return [
            'sagaClassName' => $this->sagaClassName,
            'sagaName' => $this->sagaName,
            'sagaId' => $this->sagaId->toPayload(),
            'eventSubscribers' => array_map(fn($eventSubscriber) => $eventSubscriber->toPayload(), $this->eventSubscribers),
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            $payload['sagaClassName'],
            $payload['sagaName'],
            SagaIdDefinition::fromPayload($payload['sagaId']),
            array_map(fn($eventSubscriber) => SagaEventSubscriberDefinition::fromPayload($eventSubscriber), $payload['eventSubscribers']),
        );
    }
}
