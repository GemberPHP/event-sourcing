<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\DomainTags;

use Stringable;

/**
 * Resolve all domain tag values from an event (object).
 */
interface DomainTagsResolver
{
    /**
     * @throws UnresolvableDomainTagsException
     *
     * @return list<string|Stringable>
     */
    public function resolve(object $event): array;
}
