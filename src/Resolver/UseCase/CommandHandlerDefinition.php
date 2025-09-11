<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase;

use Gember\EventSourcing\UseCase\Attribute\CreationPolicy;
use Gember\EventSourcing\Util\Serialization\Serializable;

/**
 * @phpstan-type CommandHandlerDefinitionPayload array{
 *     commandClassName: class-string,
 *     methodName: string,
 *     policy: string
 * }
 *
 * @implements Serializable<CommandHandlerDefinitionPayload, CommandHandlerDefinition>
 */
final readonly class CommandHandlerDefinition implements Serializable
{
    /**
     * @param class-string $commandClassName
     */
    public function __construct(
        public string $commandClassName,
        public string $methodName,
        public CreationPolicy $policy,
    ) {}

    public function toPayload(): array
    {
        return [
            'commandClassName' => $this->commandClassName,
            'methodName' => $this->methodName,
            'policy' => $this->policy->value,
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            $payload['commandClassName'],
            $payload['methodName'],
            CreationPolicy::from($payload['policy']),
        );
    }
}
