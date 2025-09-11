<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase\Default\CommandHandler;

use Gember\EventSourcing\Resolver\UseCase\CommandHandlerDefinition;

interface CommandHandlerResolver
{
    /**
     * @param class-string $useCaseClassName
     *
     * @return list<CommandHandlerDefinition>
     */
    public function resolve(string $useCaseClassName): array;
}
