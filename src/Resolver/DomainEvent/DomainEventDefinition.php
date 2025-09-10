<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent;

use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagDefinition;
use Gember\EventSourcing\Util\Serialization\Serializable;

/**
 * @phpstan-import-type DomainTagDefinitionPayload from DomainTagDefinition
 *
 * @phpstan-type DomainEventDefinitionPayload array{
 *     eventClassName: class-string,
 *     eventName: string,
 *     domainTags: list<DomainTagDefinitionPayload>
 * }
 *
 * @implements Serializable<DomainEventDefinitionPayload, DomainEventDefinition>
 */
final readonly class DomainEventDefinition implements Serializable
{
    /**
     * @param class-string $eventClassName
     * @param list<DomainTagDefinition> $domainTags
     */
    public function __construct(
        public string $eventClassName,
        public string $eventName,
        public array $domainTags,
    ) {}

    public function toPayload(): array
    {
        return [
            'eventClassName' => $this->eventClassName,
            'eventName' => $this->eventName,
            'domainTags' => array_map(fn($domainTag) => $domainTag->toPayload(), $this->domainTags),
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            $payload['eventClassName'],
            $payload['eventName'],
            array_map(fn($domainTagPayload) => DomainTagDefinition::fromPayload($domainTagPayload), $payload['domainTags']),
        );
    }
}
