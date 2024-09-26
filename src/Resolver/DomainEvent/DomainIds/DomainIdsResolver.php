<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\DomainIds;

use Stringable;

/**
 * Resolve all domain identifier values from an event (object).
 */
interface DomainIdsResolver
{
    /**
     * @throws UnresolvableDomainIdsException
     *
     * @return list<string|Stringable>
     */
    public function resolve(object $event): array;
}
