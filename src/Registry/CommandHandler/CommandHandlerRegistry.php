<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Registry\CommandHandler;

use Gember\EventSourcing\Resolver\UseCase\CommandHandlers\CommandHandlerDefinition;

/**
 * Retrieve command handler definition based on command name (FQCN).
 */
interface CommandHandlerRegistry
{
    /**
     * @param class-string $commandName
     *
     * @throws CommandHandlerNotRegisteredException
     */
    public function retrieve(string $commandName): CommandHandlerDefinition;
}
