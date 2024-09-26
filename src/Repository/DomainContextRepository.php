<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Repository;

use Gember\EventSourcing\DomainContext\EventSourcedDomainContext;
use Stringable;

/**
 * @template T of EventSourcedDomainContext
 */
interface DomainContextRepository
{
    /**
     * @param class-string<T> $domainContextClassName
     *
     * @throws DomainContextNotFoundException
     * @throws DomainContextRepositoryFailedException
     *
     * @return T
     */
    public function get(string $domainContextClassName, string|Stringable ...$domainId): EventSourcedDomainContext;

    /**
     * @param class-string<T> $domainContextClassName
     *
     * @throws DomainContextRepositoryFailedException
     */
    public function has(string $domainContextClassName, string|Stringable ...$domainId): bool;

    /**
     * @param T $context
     *
     * @throws DomainContextRepositoryFailedException
     */
    public function save(EventSourcedDomainContext $context): void;
}
