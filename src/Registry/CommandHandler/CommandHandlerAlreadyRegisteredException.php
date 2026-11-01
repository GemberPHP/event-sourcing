<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Registry\CommandHandler;

use Exception;

final class CommandHandlerAlreadyRegisteredException extends Exception
{
    public static function withCommandName(string $commandName, string $registeredCommandHandler, string $commandHandler): self
    {
        return new self(sprintf(
            'Command `%s` is already handled by %s, and can\'t be handled by %s too',
            $commandName,
            $registeredCommandHandler,
            $commandHandler,
        ));
    }
}
