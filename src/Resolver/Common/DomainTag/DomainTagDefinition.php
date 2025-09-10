<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Common\DomainTag;

use Gember\EventSourcing\Util\Serialization\Serializable;

/**
 * @phpstan-type DomainTagDefinitionPayload array{
 *     domainTagName: string,
 *     type: string
 * }
 *
 * @implements Serializable<DomainTagDefinitionPayload, DomainTagDefinition>
 */
final readonly class DomainTagDefinition implements Serializable
{
    public function __construct(
        public string $domainTagName,
        public DomainTagType $type,
    ) {}

    public function toPayload(): array
    {
        return [
            'domainTagName' => $this->domainTagName,
            'type' => $this->type->value,
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            $payload['domainTagName'],
            DomainTagType::from($payload['type']),
        );
    }
}
