<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent;

use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagDefinition;
use Gember\EventSourcing\Resolver\Common\SagaId\SagaIdDefinition;
use Gember\EventSourcing\Util\Serialization\Serializable;

/**
 * @phpstan-import-type DomainTagDefinitionPayload from DomainTagDefinition
 * @phpstan-import-type SagaIdDefinitionPayload from SagaIdDefinition
 *
 * @phpstan-type DomainEventDefinitionPayload array{
 *     eventClassName: class-string,
 *     eventName: string,
 *     domainTags: list<DomainTagDefinitionPayload>,
 *     sagaIds: list<SagaIdDefinitionPayload>
 * }
 *
 * @implements Serializable<DomainEventDefinitionPayload, DomainEventDefinition>
 */
final readonly class DomainEventDefinition implements Serializable
{
    /**
     * @param class-string $eventClassName
     * @param list<DomainTagDefinition> $domainTags
     * @param list<SagaIdDefinition> $sagaIds
     */
    public function __construct(
        public string $eventClassName,
        public string $eventName,
        public array $domainTags,
        public array $sagaIds,
    ) {}

    public function toPayload(): array
    {
        return [
            'eventClassName' => $this->eventClassName,
            'eventName' => $this->eventName,
            'domainTags' => array_map(fn($domainTag) => $domainTag->toPayload(), $this->domainTags),
            'sagaIds' => array_map(fn($sagaId) => $sagaId->toPayload(), $this->sagaIds),
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            $payload['eventClassName'],
            $payload['eventName'],
            array_map(fn($domainTagPayload) => DomainTagDefinition::fromPayload($domainTagPayload), $payload['domainTags']),
            array_map(fn($sagaIdPayload) => SagaIdDefinition::fromPayload($sagaIdPayload), $payload['sagaIds']),
        );
    }
}
