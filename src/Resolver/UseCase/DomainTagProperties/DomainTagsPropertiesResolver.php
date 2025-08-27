<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase\DomainTagProperties;

use Gember\EventSourcing\UseCase\EventSourcedUseCase;

/**
 * Resolves property names defining all domain tags belonging to the use case.
 */
interface DomainTagsPropertiesResolver
{
    /**
     * @param class-string<EventSourcedUseCase> $useCaseClassName
     *
     * @throws UnresolvableDomainTagPropertiesException
     *
     * @return list<string>
     */
    public function resolve(string $useCaseClassName): array;
}
