<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase\CommandHandlers;

use Gember\EventSourcing\UseCase\EventSourcedUseCase;

interface CommandHandlersResolver
{
    /**
     * @param class-string<EventSourcedUseCase> $useCaseClassName
     *
     * @return list<CommandHandlerDefinition>
     */
    public function resolve(string $useCaseClassName): array;
}
