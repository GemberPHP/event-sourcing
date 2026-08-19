<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Saga;

use Gember\DependencyContracts\Util\Messaging\MessageBus\CommandBus;

final class CommandRecorder
{
    /**
     * @var list<object>
     */
    private array $recordedCommands = [];

    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    public function record(object $command): void
    {
        $this->recordedCommands[] = $command;
    }

    public function flush(): void
    {
        $commands = $this->recordedCommands;
        $this->recordedCommands = [];

        foreach ($commands as $command) {
            $this->commandBus->handle($command);
        }
    }
}
