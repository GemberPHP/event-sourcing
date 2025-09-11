<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Registry\CommandHandler;

use Gember\EventSourcing\Resolver\UseCase\CommandHandlerDefinition;
use Gember\EventSourcing\UseCase\EventSourcedUseCase;

/**
 * Retrieve command handler definition based on command name (FQCN).
 */
interface CommandHandlerRegistry
{
    /**
     * @param class-string $commandName
     *
     * @throws CommandHandlerNotRegisteredException
     *
     * @return array{class-string<EventSourcedUseCase>, CommandHandlerDefinition}
     */
    public function retrieve(string $commandName): array;
}
