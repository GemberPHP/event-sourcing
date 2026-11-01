<?php

declare(strict_types=1);

namespace Gember\EventSourcing\UseCase\CommandHandler\Default;

use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Repository\InvalidUseCaseDomainTagsException;
use Gember\EventSourcing\Repository\UseCaseNotFoundException;
use Gember\EventSourcing\Repository\UseCaseRepository;
use Gember\EventSourcing\Repository\UseCaseRepositoryFailedException;
use Gember\EventSourcing\Resolver\UseCase\CommandHandlerDefinition;
use Gember\EventSourcing\UseCase\CommandHandler\UseCaseCommandExecutor;
use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Stringable;
use Override;

final readonly class DefaultUseCaseCommandExecutor implements UseCaseCommandExecutor
{
    public function __construct(
        private UseCaseRepository $repository,
    ) {}

    /**
     * @throws InvalidUseCaseDomainTagsException
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

        $this->guardMatchingDomainTags($useCase, $useCaseClassName, $domainTags);

        $this->repository->save($useCase);
    }

    /**
     * The command's domain tags load the use case's events, the use case's domain tags lock them when saving:
     * when they differ, the optimistic lock no longer guards the events the decision was based on.
     *
     * @param class-string $useCaseClassName
     * @param array<string|Stringable> $commandDomainTags
     *
     * @throws InvalidUseCaseDomainTagsException
     */
    private function guardMatchingDomainTags(
        EventSourcedUseCase $useCase,
        string $useCaseClassName,
        array $commandDomainTags,
    ): void {
        $commandDomainTags = $this->normalizeDomainTags($commandDomainTags);
        $useCaseDomainTags = $this->normalizeDomainTags($useCase->getDomainTags());

        if ($commandDomainTags !== $useCaseDomainTags) {
            throw InvalidUseCaseDomainTagsException::mismatch($useCaseClassName, $commandDomainTags, $useCaseDomainTags);
        }
    }

    /**
     * @param array<string|Stringable> $domainTags
     *
     * @return list<string>
     */
    private function normalizeDomainTags(array $domainTags): array
    {
        $domainTags = array_unique(array_map(strval(...), $domainTags));
        sort($domainTags);

        return $domainTags;
    }
}
