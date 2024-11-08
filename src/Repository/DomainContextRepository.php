<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Repository;

use Gember\EventSourcing\DomainContext\EventSourcedDomainContext;
use Stringable;

interface DomainContextRepository
{
    /**
     * @template T of EventSourcedDomainContext
     *
     * @param class-string<T> $domainContextClassName
     *
     * @throws DomainContextNotFoundException
     * @throws DomainContextRepositoryFailedException
     *
     * @return T
     */
    public function get(string $domainContextClassName, string|Stringable ...$domainId): EventSourcedDomainContext;

    /**
     * @template T of EventSourcedDomainContext
     *
     * @param class-string<T> $domainContextClassName
     *
     * @throws DomainContextRepositoryFailedException
     */
    public function has(string $domainContextClassName, string|Stringable ...$domainId): bool;

    /**
     * @throws DomainContextRepositoryFailedException
     */
    public function save(EventSourcedDomainContext $context): void;
}
