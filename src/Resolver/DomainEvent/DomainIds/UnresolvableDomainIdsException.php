<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\DomainIds;

use Exception;

class UnresolvableDomainIdsException extends Exception
{
    /**
     * @param class-string $eventClassName
     */
    public static function create(string $eventClassName, string $message): self
    {
        return new self(sprintf('Unresolvable domainIds for event %s: %s', $eventClassName, $message));
    }
}
