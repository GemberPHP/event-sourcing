<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainMessage\DomainTags;

use Stringable;

/**
 * Resolve all domain tag values from a message (event/command object).
 */
interface DomainTagsResolver
{
    /**
     * @throws UnresolvableDomainTagsException
     *
     * @return list<string|Stringable>
     */
    public function resolve(object $message): array;
}
