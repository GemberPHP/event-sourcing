<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Repository;

use Gember\EventSourcing\Repository\UseCaseNotFoundException;
use Gember\EventSourcing\Repository\UseCaseRepository;
use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Stringable;

final class TestUseCaseRepository implements UseCaseRepository
{
    /**
     * @var array<string, array<string, EventSourcedUseCase>>
     */
    private array $useCases = [];

    public function get(string $useCaseClassName, string|Stringable ...$domainTag): EventSourcedUseCase
    {
        if (!$this->has($useCaseClassName, ...$domainTag)) {
            throw UseCaseNotFoundException::create();
        }

        return $this->useCases[implode('', $domainTag)][$useCaseClassName];
    }

    public function has(string $useCaseClassName, string|Stringable ...$domainTag): bool
    {
        return isset($this->useCases[implode('', $domainTag)][$useCaseClassName]);
    }

    public function save(EventSourcedUseCase $useCase): void
    {
        $this->useCases[implode('', $useCase->getDomainTags())][$useCase::class] = $useCase;
    }
}
