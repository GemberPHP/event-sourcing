<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase\DomainIdProperties;

use Gember\EventSourcing\UseCase\EventSourcedUseCase;

/**
 * Resolves property names defining all domain identifiers belonging to the use case.
 */
interface DomainIdPropertiesResolver
{
    /**
     * @param class-string<EventSourcedUseCase> $useCaseClassName
     *
     * @throws UnresolvableDomainIdPropertiesException
     *
     * @return list<string>
     */
    public function resolve(string $useCaseClassName): array;
}
