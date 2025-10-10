<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Common\SagaId;

use Gember\EventSourcing\Util\Serialization\Serializable;

/**
 * @phpstan-type SagaIdDefinitionPayload array{
 *     sagaIdName: string,
 *     propertyName: string
 * }
 *
 * @implements Serializable<SagaIdDefinitionPayload, SagaIdDefinition>
 */
final readonly class SagaIdDefinition implements Serializable
{
    public function __construct(
        public string $sagaIdName,
        public string $propertyName,
    ) {}

    public function toPayload(): array
    {
        return [
            'sagaIdName' => $this->sagaIdName,
            'propertyName' => $this->propertyName,
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self($payload['sagaIdName'], $payload['propertyName']);
    }
}
