<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Registry\CommandHandler;

use Exception;

final class CommandHandlerNotRegisteredException extends Exception
{
    public static function withCommandName(string $commandName): self
    {
        return new self(sprintf('Command handler for command `%s` not registered', $commandName));
    }
}
