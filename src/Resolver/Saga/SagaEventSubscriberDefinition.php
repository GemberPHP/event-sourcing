<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Saga;

use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Util\Serialization\Serializable;

/**
 * @phpstan-type SagaEventSubscriberDefinitionPayload array{
 *     eventClassName: class-string,
 *     methodName: string,
 *     policy: string
 * }
 *
 * @implements Serializable<SagaEventSubscriberDefinitionPayload, SagaEventSubscriberDefinition>
 */
final readonly class SagaEventSubscriberDefinition implements Serializable
{
    /**
     * @param class-string $eventClassName
     */
    public function __construct(
        public string $eventClassName,
        public string $methodName,
        public CreationPolicy $policy,
    ) {}

    public function toPayload(): array
    {
        return [
            'eventClassName' => $this->eventClassName,
            'methodName' => $this->methodName,
            'policy' => $this->policy->value,
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            $payload['eventClassName'],
            $payload['methodName'],
            CreationPolicy::from($payload['policy']),
        );
    }
}
