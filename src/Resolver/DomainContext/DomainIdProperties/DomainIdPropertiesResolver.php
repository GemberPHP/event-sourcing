<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainContext\DomainIdProperties;

use Gember\EventSourcing\DomainContext\EventSourcedDomainContext;

/**
 * Resolves property names defining all domain identifiers belonging to the domain context.
 *
 * @template T of EventSourcedDomainContext
 */
interface DomainIdPropertiesResolver
{
    /**
     * @param class-string<EventSourcedDomainContext<T>> $domainContextClassName
     *
     * @throws UnresolvableDomainIdPropertiesException
     *
     * @return list<string>
     */
    public function resolve(string $domainContextClassName): array;
}