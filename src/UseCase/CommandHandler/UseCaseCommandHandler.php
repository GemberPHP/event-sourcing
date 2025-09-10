<?php

declare(strict_types=1);

namespace Gember\EventSourcing\UseCase\CommandHandler;

use Gember\EventSourcing\Registry\CommandHandler\CommandHandlerNotRegisteredException;
use Gember\EventSourcing\Registry\CommandHandler\CommandHandlerRegistry;
use Gember\EventSourcing\Repository\UseCaseNotFoundException;
use Gember\EventSourcing\Repository\UseCaseRepository;
use Gember\EventSourcing\Repository\UseCaseRepositoryFailedException;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagValueHelper;
use Gember\EventSourcing\Resolver\DomainCommand\DomainCommandResolver;
use Gember\EventSourcing\UseCase\Attribute\CreationPolicy;

final readonly class UseCaseCommandHandler
{
    public function __construct(
        private UseCaseRepository $repository,
        private CommandHandlerRegistry $commandHandlerRegistry,
        private DomainCommandResolver $domainCommandResolver,
    ) {}

    /**
     * @throws UseCaseNotFoundException
     * @throws UseCaseRepositoryFailedException
     * @throws CommandHandlerNotRegisteredException
     */
    public function __invoke(object $command): void
    {
        $commandHandlerDefinition = $this->commandHandlerRegistry->retrieve($command::class);

        $useCaseClassName = $commandHandlerDefinition->useCaseClassName;
        $methodName = $commandHandlerDefinition->methodName;

        try {
            $useCase = $this->repository->get(
                $useCaseClassName,
                ...DomainTagValueHelper::getDomainTagValues(
                    $command,
                    $this->domainCommandResolver->resolve($command::class)->domainTags,
                ),
            );
        } catch (UseCaseNotFoundException $exception) {
            if ($commandHandlerDefinition->policy !== CreationPolicy::IfMissing) {
                throw $exception;
            }

            $useCase = new $useCaseClassName();
        }

        $useCase->{$methodName}($command);

        $this->repository->save($useCase);
    }
}
