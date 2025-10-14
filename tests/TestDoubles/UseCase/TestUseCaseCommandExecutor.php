<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\UseCase;

use Exception;
use Gember\EventSourcing\Resolver\UseCase\CommandHandlerDefinition;
use Gember\EventSourcing\UseCase\CommandHandler\UseCaseCommandExecutor;
use Stringable;

/**
 * @internal
 */
final class TestUseCaseCommandExecutor implements UseCaseCommandExecutor
{
    /**
     * @param list<string|Stringable> $lastDomainTags
     */
    public function __construct(
        public bool $wasExecuted = false,
        public ?object $lastCommand = null,
        public ?CommandHandlerDefinition $lastCommandHandlerDefinition = null,
        public ?string $lastUseCaseClassName = null,
        public ?string $lastMethodName = null,
        public array $lastDomainTags = [],
        public ?Exception $shouldThrow = null,
    ) {}

    public function execute(
        object $command,
        CommandHandlerDefinition $commandHandlerDefinition,
        string $useCaseClassName,
        string $methodName,
        string|Stringable ...$domainTags,
    ): void {
        $this->wasExecuted = true;
        $this->lastCommand = $command;
        $this->lastCommandHandlerDefinition = $commandHandlerDefinition;
        $this->lastUseCaseClassName = $useCaseClassName;
        $this->lastMethodName = $methodName;
        $this->lastDomainTags = array_values($domainTags);

        if ($this->shouldThrow !== null) {
            throw $this->shouldThrow;
        }
    }
}
