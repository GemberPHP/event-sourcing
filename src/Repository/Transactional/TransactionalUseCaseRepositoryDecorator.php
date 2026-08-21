<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Repository\Transactional;

use Gember\DependencyContracts\Util\Transaction\Transactional;
use Gember\EventSourcing\Repository\UseCaseRepository;
use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Override;
use Stringable;

final readonly class TransactionalUseCaseRepositoryDecorator implements UseCaseRepository
{
    public function __construct(
        private UseCaseRepository $useCaseRepository,
        private Transactional $transactional,
    ) {}

    #[Override]
    public function get(string $useCaseClassName, string|Stringable ...$domainTag): EventSourcedUseCase
    {
        return $this->useCaseRepository->get($useCaseClassName, ...$domainTag);
    }

    #[Override]
    public function has(string $useCaseClassName, string|Stringable ...$domainTag): bool
    {
        return $this->useCaseRepository->has($useCaseClassName, ...$domainTag);
    }

    #[Override]
    public function save(EventSourcedUseCase $useCase): void
    {
        $this->transactional->transactional(fn() => $this->useCaseRepository->save($useCase));
    }
}
