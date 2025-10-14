<?php

declare(strict_types=1);

namespace Gember\EventSourcing\UseCase\CommandHandler;

use Gember\EventSourcing\Resolver\UseCase\CommandHandlerDefinition;
use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Stringable;

interface UseCaseCommandExecutor
{
    /**
     * @param class-string<EventSourcedUseCase> $useCaseClassName
     */
    public function execute(
        object $command,
        CommandHandlerDefinition $commandHandlerDefinition,
        string $useCaseClassName,
        string $methodName,
        string|Stringable ...$domainTags,
    ): void;
}
