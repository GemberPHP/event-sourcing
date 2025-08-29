<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase\CommandHandlers;

use Gember\EventSourcing\UseCase\Attribute\CreationPolicy;
use Gember\EventSourcing\UseCase\EventSourcedUseCase;

/**
 * @phpstan-type CommandHandlerDefinitionPayload array{
 *     commandName: class-string,
 *     useCaseClassName: class-string<EventSourcedUseCase>,
 *     methodName: string,
 *     policy: string
 * }
 */
final readonly class CommandHandlerDefinition
{
    /**
     * @param class-string $commandName
     * @param class-string<EventSourcedUseCase> $useCaseClassName
     */
    public function __construct(
        public string $commandName,
        public string $useCaseClassName,
        public string $methodName,
        public CreationPolicy $policy,
    ) {}

    /**
     * @param CommandHandlerDefinitionPayload $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            $payload['commandName'],
            $payload['useCaseClassName'],
            $payload['methodName'],
            CreationPolicy::from($payload['policy']),
        );
    }

    /**
     * @return CommandHandlerDefinitionPayload
     */
    public function toPayload(): array
    {
        return [
            'commandName' => $this->commandName,
            'useCaseClassName' => $this->useCaseClassName,
            'methodName' => $this->methodName,
            'policy' => $this->policy->value,
        ];
    }
}
