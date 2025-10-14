<?php

declare(strict_types=1);

namespace Gember\EventSourcing\UseCase\CommandHandler;

use Gember\EventSourcing\Registry\CommandHandler\CommandHandlerNotRegisteredException;
use Gember\EventSourcing\Registry\CommandHandler\CommandHandlerRegistry;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagValueHelper;
use Gember\EventSourcing\Resolver\DomainCommand\DomainCommandResolver;

final readonly class UseCaseCommandHandler
{
    public function __construct(
        private CommandHandlerRegistry $commandHandlerRegistry,
        private DomainCommandResolver $domainCommandResolver,
        private UseCaseCommandExecutor $useCaseCommandExecutor,
    ) {}

    /**
     * @throws CommandHandlerNotRegisteredException
     */
    public function __invoke(object $command): void
    {
        [$useCaseClassName, $commandHandlerDefinition] = $this->commandHandlerRegistry->retrieve($command::class);

        $methodName = $commandHandlerDefinition->methodName;

        $domainTags = DomainTagValueHelper::getDomainTagValues(
            $command,
            $this->domainCommandResolver->resolve($command::class)->domainTags,
        );

        $this->useCaseCommandExecutor->execute(
            $command,
            $commandHandlerDefinition,
            $useCaseClassName,
            $methodName,
            ...$domainTags,
        );
    }
}
