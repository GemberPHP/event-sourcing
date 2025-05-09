<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Repository;

use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Stringable;

interface UseCaseRepository
{
    /**
     * @template T of EventSourcedUseCase
     *
     * @param class-string<T> $useCaseClassName
     *
     * @throws UseCaseRepositoryFailedException
     * @throws UseCaseNotFoundException
     *
     * @return T
     */
    public function get(string $useCaseClassName, string|Stringable ...$domainId): EventSourcedUseCase;

    /**
     * @template T of EventSourcedUseCase
     *
     * @param class-string<T> $useCaseClassName
     *
     * @throws UseCaseRepositoryFailedException
     */
    public function has(string $useCaseClassName, string|Stringable ...$domainId): bool;

    /**
     * @throws UseCaseRepositoryFailedException
     */
    public function save(EventSourcedUseCase $useCase): void;
}
