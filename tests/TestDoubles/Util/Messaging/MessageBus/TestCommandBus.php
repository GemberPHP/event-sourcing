<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Util\Messaging\MessageBus;

use Gember\DependencyContracts\Util\Messaging\MessageBus\CommandBus;

final class TestCommandBus implements CommandBus
{
    /**
     * @var list<object>
     */
    public array $commands = [];

    public function handle(object $command): void
    {
        $this->commands[] = $command;
    }
}
