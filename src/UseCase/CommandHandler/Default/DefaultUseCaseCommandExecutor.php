<?php

declare(strict_types=1);

namespace Gember\EventSourcing\UseCase\CommandHandler\Default;

use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Repository\UseCaseNotFoundException;
use Gember\EventSourcing\Repository\UseCaseRepository;
use Gember\EventSourcing\Repository\UseCaseRepositoryFailedException;
use Gember\EventSourcing\Resolver\UseCase\CommandHandlerDefinition;
use Gember\EventSourcing\UseCase\CommandHandler\UseCaseCommandExecutor;
use Stringable;
use Override;

final readonly class DefaultUseCaseCommandExecutor implements UseCaseCommandExecutor
{
    public function __construct(
        private UseCaseRepository $repository,
    ) {}

    /**
     * @throws UseCaseNotFoundException
     * @throws UseCaseRepositoryFailedException
     */
    #[Override]
    public function execute(
        object $command,
        CommandHandlerDefinition $commandHandlerDefinition,
        string $useCaseClassName,
        string $methodName,
        string|Stringable ...$domainTags,
    ): void {
        try {
            $useCase = $this->repository->get($useCaseClassName, ...$domainTags);
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
